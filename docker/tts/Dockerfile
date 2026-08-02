# ---------------------------------------------------------------------------
# Neural TTS sidecar (M31) — fully offline at runtime:
#   English — Piper (real male/female voices, ONNX)
#   Bangla  — Meta MMS-TTS (facebook/mms-tts-ben, VITS)
# Models are baked into the image at build time; no internet needed after.
# ---------------------------------------------------------------------------
FROM python:3.11-slim

# CPU-only torch (the default wheel bundles CUDA and is ~5x larger).
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu \
 && pip install --no-cache-dir fastapi uvicorn transformers numpy piper-tts

# Bake the Bengali model + tokenizer into the image.
ENV HF_HOME=/hf
RUN python -c "from transformers import VitsModel, AutoTokenizer; \
VitsModel.from_pretrained('facebook/mms-tts-ben'); \
AutoTokenizer.from_pretrained('facebook/mms-tts-ben')"

# Piper English voices — Ryan (male), Amy (female), medium quality.
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/ryan/medium/en_US-ryan-medium.onnx /models/en_US-ryan-medium.onnx
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/ryan/medium/en_US-ryan-medium.onnx.json /models/en_US-ryan-medium.onnx.json
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/amy/medium/en_US-amy-medium.onnx /models/en_US-amy-medium.onnx
ADD https://huggingface.co/rhasspy/piper-voices/resolve/main/en/en_US/amy/medium/en_US-amy-medium.onnx.json /models/en_US-amy-medium.onnx.json

COPY app.py /srv/app.py
WORKDIR /srv

EXPOSE 5002
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "5002"]
