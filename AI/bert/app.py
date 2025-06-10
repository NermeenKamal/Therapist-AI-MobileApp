from fastapi import FastAPI, Request
import requests
import os

app = FastAPI()

HF_API_URL = "https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english"
HF_TOKEN = os.getenv("HF_TOKEN")

headers = {"Authorization": f"Bearer {HF_TOKEN}"}

@app.get("/health")
def health_check():
    return {"status": "ok", "service": "bert", "huggingface_model": HF_API_URL}


@app.get("/test-model")
def test_model():
    headers = {"Authorization": f"Bearer {HF_TOKEN}"}
    hf_url = HF_API_URL

    sample_input = {"inputs": "You are a great doctor!"}

    try:
        response = requests.post(hf_url, headers=headers, json=sample_input)

        # نطبع الحالة والنص الكامل
        print("🔍 STATUS:", response.status_code)
        print("🔍 TEXT:", response.text)

        # نجرب نرجع كل البيانات الخام
        return {
            "status_code": response.status_code,
            "text": response.text,
            "json": response.json() if response.headers.get('Content-Type') == 'application/json' else None
        }

    except Exception as e:
        return {
            "error": "Failed to parse Hugging Face response",
            "detail": str(e),
            "status_code": response.status_code,
            "raw": response.text
        }


@app.get("/debug")
def debug():
    return {
        "hf_token_set": bool(HF_TOKEN),
        "endpoint": HF_API_URL
    }
def root():
    return {"message": "Sentiment model is live 🎯"}

@app.post("/predict")
async def predict(request: Request):
    data = await request.json()
    text = data.get("inputs")
    if not text:
        return {"error": "Missing text"}

    response = requests.post(HF_API_URL, headers=headers, json={"inputs": text})

    # DEBUGGING:
    print("🔍 STATUS:", response.status_code)
    print("🔍 TEXT:", response.text)

    try:
        result = response.json()
    except Exception as e:
        return {
            "error": "Failed to parse Hugging Face response",
            "detail": str(e),
            "status_code": response.status_code,
            "raw": response.text
        }

    return result
