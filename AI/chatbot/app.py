from fastapi import FastAPI, Request
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM
import torch

app = FastAPI()

# تحميل الموديل والتوكنيزر مرة واحدة عند تشغيل السيرفر
model_id = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"
tokenizer = AutoTokenizer.from_pretrained(model_id)
model = AutoModelForSeq2SeqLM.from_pretrained(model_id)

@app.get("/")
def root():
    return {"message": "Chatbot is live 🧠"}

@app.post("/chat/send-message")
async def chat(request: Request):
    data = await request.json()
    message = data.get("message")

    if not message:
        return {"error": "Missing 'message' in request"}

    try:
        # الترميز
        inputs = tokenizer.encode(message, return_tensors="pt")

        # التوليد
        outputs = model.generate(inputs, max_new_tokens=100)

        # فك الترميز
        decoded = tokenizer.decode(outputs[0], skip_special_tokens=True)

        return {"response": decoded}
    except Exception as e:
        return {"error": "Model error", "detail": str(e)}

@app.get("/health")
def health():
    return {"status": "ok", "model": model_id}
