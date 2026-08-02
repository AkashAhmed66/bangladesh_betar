"""Neural TTS sidecar for the Bangladesh Betar admin portal (M31).

POST /synthesize {text, language: en|bn, voice: male|female} -> WAV bytes.
English uses Piper (distinct male/female voices); Bangla uses Meta MMS-TTS
(single neural voice — the Laravel side derives the second gender by pitch
shifting). Long texts are chunked by sentence and concatenated.
"""

import io
import re
import wave

import numpy as np
import torch
from fastapi import FastAPI, HTTPException
from fastapi.responses import Response
from piper import PiperVoice
from pydantic import BaseModel
from transformers import AutoTokenizer, VitsModel

app = FastAPI()

EN_VOICES = {
    "male": PiperVoice.load("/models/en_US-ryan-medium.onnx", config_path="/models/en_US-ryan-medium.onnx.json"),
    "female": PiperVoice.load("/models/en_US-amy-medium.onnx", config_path="/models/en_US-amy-medium.onnx.json"),
}

BN_MODEL = VitsModel.from_pretrained("facebook/mms-tts-ben")
BN_TOKENIZER = AutoTokenizer.from_pretrained("facebook/mms-tts-ben")
BN_MODEL.eval()
BN_RATE = BN_MODEL.config.sampling_rate


class SynthesizeRequest(BaseModel):
    text: str
    language: str  # en | bn
    voice: str = "female"  # male | female


def sentence_chunks(text: str, limit: int = 400):
    """Split on sentence boundaries (Bangla danda included), pack to <= limit chars."""
    parts = re.split(r"(?<=[।.!?\n])\s+", text)
    buf = ""
    for part in parts:
        part = part.strip()
        if not part:
            continue
        if buf and len(buf) + len(part) + 1 > limit:
            yield buf
            buf = part
        else:
            buf = f"{buf} {part}".strip()
    if buf:
        yield buf


def to_wav_bytes(samples: np.ndarray, rate: int) -> bytes:
    pcm = (np.clip(samples, -1.0, 1.0) * 32767).astype(np.int16)
    out = io.BytesIO()
    with wave.open(out, "wb") as w:
        w.setnchannels(1)
        w.setsampwidth(2)
        w.setframerate(rate)
        w.writeframes(pcm.tobytes())
    return out.getvalue()


@app.get("/health")
def health():
    return {"ok": True, "engines": {"en": "piper (ryan/amy)", "bn": "mms-tts-ben"}}


@app.post("/synthesize")
def synthesize(req: SynthesizeRequest):
    text = req.text.strip()
    if not text:
        raise HTTPException(422, "Empty text.")

    if req.language == "bn":
        pieces = []
        gap = np.zeros(int(BN_RATE * 0.25), dtype=np.float32)
        for chunk in sentence_chunks(text):
            inputs = BN_TOKENIZER(chunk, return_tensors="pt")
            with torch.no_grad():
                waveform = BN_MODEL(**inputs).waveform[0].numpy()
            pieces.extend((waveform.astype(np.float32), gap))
        if not pieces:
            raise HTTPException(422, "Nothing to synthesize.")
        return Response(content=to_wav_bytes(np.concatenate(pieces), BN_RATE), media_type="audio/wav")

    # English — Piper. API differs between generations: 1.3+ has
    # synthesize_wav(text, wav); older versions stream via synthesize(text).
    voice = EN_VOICES["male" if req.voice == "male" else "female"]
    out = io.BytesIO()
    with wave.open(out, "wb") as wav_file:
        if hasattr(voice, "synthesize_wav"):
            voice.synthesize_wav(text, wav_file)
        else:
            rate = getattr(getattr(voice, "config", None), "sample_rate", 22050)
            wav_file.setnchannels(1)
            wav_file.setsampwidth(2)
            wav_file.setframerate(rate)
            for chunk in voice.synthesize(text):
                data = getattr(chunk, "audio_int16_bytes", None)
                if data is None and isinstance(chunk, (bytes, bytearray)):
                    data = bytes(chunk)
                if data:
                    wav_file.writeframes(data)
    return Response(content=out.getvalue(), media_type="audio/wav")
