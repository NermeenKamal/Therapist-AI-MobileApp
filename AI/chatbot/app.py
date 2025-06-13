from fastapi import FastAPI, Request
import os
from huggingface_hub import InferenceClient

app = FastAPI()

HF_MODEL_ID = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"  
HF_TOKEN = os.getenv("HF_TOKEN")  

client = InferenceClient(
    model=HF_MODEL_ID,
    token=HF_TOKEN,
)

@app.get("/")
def root():
    return {"message": "Chatbot is live 🧠"}

@app.post("/chat/send-message")
async def send_message(request: Request):
    data = await request.json()
    message = data.get("message")

    if not message:
        return {"error": "Missing 'message' in request"}

    try:
        response = client.text_generation(prompt=message, max_new_tokens=100)
        return {"response": response}
    except Exception as e:
        return {
            "error": "Failed to get response from model",
            "detail": str(e)
        }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model": HF_MODEL_ID,
        "token_set": bool(HF_TOKEN)
    }
