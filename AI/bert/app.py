from fastapi import FastAPI, Request
import requests
import os

app = FastAPI()

HF_API_URL = "https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english"
HF_TOKEN = os.getenv("HF_TOKEN")

headers = {"Authorization": f"Bearer {HF_TOKEN}"}

@app.get("/")
def root():
    return {"message": "Sentiment model is live 🎯"}

@app.post("/predict")
async def predict(request: Request):
    data = await request.json()
    text = data.get("text", "")
    if not text:
        return {"error": "Missing text"}

    response = requests.post(HF_API_URL, headers=headers, json={"inputs": text})
    print("Raw response from HF:", response.status_code, response.text)

    try:
        result = response.json()
    except Exception as e:
        return {
            "error": "Failed to parse Hugging Face response",
            "detail": str(e)
        }

    return result
