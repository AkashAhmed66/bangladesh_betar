# ---------------------------------------------------------------------------
# Neural TTS sidecar (M31) — fully offline, Piper-class lightweight runtime:
#   English — Piper (Ryan/Amy, ONNX)
#   Bangla  — BanglaTTS (bangla-speech-processing, MIT): REAL male + female
#             Bangladeshi VITS voices, converted to ONNX at build time.
# The heavy PyTorch/Coqui stack exists only in a throwaway build stage; the
# final image runs everything on onnxruntime.
# ---------------------------------------------------------------------------

# ---- Stage 1 (discarded): convert BanglaTTS checkpoints to ONNX ----------
FROM python:3.11-slim AS convert

ADD https://huggingface.co/bangla-speech-processing/bangla_tts_male/resolve/main/pytorch_model.pth /dl/bn_male.pth
ADD https://huggingface.co/bangla-speech-processing/bangla_tts_male/resolve/main/config.json /dl/bn_male_config.json
ADD https://huggingface.co/bangla-speech-processing/bangla_tts_female/resolve/main/pytorch_model.pth /dl/bn_female.pth
ADD https://huggingface.co/bangla-speech-processing/bangla_tts_female/resolve/main/config.json /dl/bn_female_config.json

# torch is pinned to 2.4.x here on purpose: VITS ONNX export needs the
# legacy TorchScript exporter (torch 2.9's dynamo exporter cannot trace it),
# and pre-2.9 torchaudio still has built-in IO (no torchcodec needed).
# This stage is discarded — none of this reaches the runtime image.
RUN apt-get update && apt-get install -y --no-install-recommends ffmpeg && rm -rf /var/lib/apt/lists/* \
 && pip install --no-cache-dir torch==2.4.1 torchaudio==2.4.1 --index-url https://download.pytorch.org/whl/cpu \
 && pip install --no-cache-dir coqui-tts "transformers>=4.42,<4.50" onnxruntime onnx

COPY convert.py /convert.py
RUN mkdir -p /out && python /convert.py

# ---- Stage 2: slim runtime (onnxruntime only, no PyTorch) ----------------
FROM python:3.11-slim

RUN pip install --no-cache-dir fastapi uvicorn numpy onnxruntime piper-tts

COPY --from=convert /out /models

# Piper English voices — Ryan (male), Amy (female), medium quality.
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/ryan/medium/en_US-ryan-medium.onnx /models/en_US-ryan-medium.onnx
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/ryan/medium/en_US-ryan-medium.onnx.json /models/en_US-ryan-medium.onnx.json
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/amy/medium/en_US-amy-medium.onnx /models/en_US-amy-medium.onnx
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/amy/medium/en_US-amy-medium.onnx.json /models/en_US-amy-medium.onnx.json

COPY app.py /srv/app.py
WORKDIR /srv

EXPOSE 5002
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "5002"]
