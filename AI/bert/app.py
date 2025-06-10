from fastapi import FastAPI, Request
import requests
import os

app = FastAPI()

HF_API_URL = "https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english"
HF_TOKEN = os.getenv("HF_TOKEN")

# تجهيز الهيدر بالتوكن
headers = {"Authorization": f"Bearer {HF_TOKEN}"}

@app.get("/")
def root():
    return {"message": "Model via HuggingFace API is ready"}

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
        if isinstance(result, list) and len(result) > 0:
            sentiment = result[0]
            feedback = None
            if sentiment['label'] == "POSITIVE":
                feedback = "Great! Keep it up."
            elif sentiment['label'] == "NEGATIVE":
                feedback = "Sorry to hear that. We're here to help."

            return {
                "label": sentiment["label"],
                "score": sentiment["score"],
                "feedback": feedback
            }
        else:
            return {"error": "Unexpected response format", "raw": result}
    except Exception as e:
        return {"error": "Failed to parse Hugging Face response", "detail": str(e)}
