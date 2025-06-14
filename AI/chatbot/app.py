from fastapi import FastAPI, Request
import os
import requests

app = FastAPI()

HF_MODEL_ID = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"
HF_TOKEN = os.getenv("HF_TOKEN")

@app.post("/chat/send-message")
async def chat(request: Request):
    data = await request.json()
    message = data.get("message")

    if not message:
        return {"error": "Missing 'message' in request"}

    try:
        response = requests.post(
            f"https://api-inference.huggingface.co/models/{HF_MODEL_ID}",
            headers={
                "Authorization": f"Bearer {HF_TOKEN}",
                "Content-Type": "application/json"
            },
            json={"inputs": f"User: {message}"}
        )

        if response.status_code != 200:
            return {"error": "Model error", "detail": response.json()}

        generated = response.json()[0]["generated_text"]
        return {"response": generated}

    except Exception as e:
        return {
            "error": "Failed to contact HuggingFace model",
            "detail": str(e)
        }
