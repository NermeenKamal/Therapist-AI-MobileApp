# from fastapi import FastAPI, Request
# import torch
# from transformers import DistilBertTokenizer, DistilBertForSequenceClassification

# app = FastAPI()

# # ✅ تحميل الموديل والـ tokenizer مرة واحدة عند بدء التشغيل
# tokenizer = DistilBertTokenizer.from_pretrained("distilbert-base-uncased-finetuned-sst-2-english")
# model = DistilBertForSequenceClassification.from_pretrained("distilbert-base-uncased-finetuned-sst-2-english")

# @app.get("/health")
# def health_check():
#     return {"status": "ok", "service": "bert", "model": "distilbert-base-uncased-finetuned-sst-2-english"}

# @app.get("/test-model")
# def test_model():
#     sample_text = "You are a great doctor!"
#     inputs = tokenizer(sample_text, return_tensors="pt")
#     with torch.no_grad():
#         logits = model(**inputs).logits
#     predicted_class_id = logits.argmax().item()
#     label = model.config.id2label[predicted_class_id]
#     score = torch.nn.functional.softmax(logits, dim=1).max().item()

#     return {
#         "input": sample_text,
#         "label": label,
#         "score": score
#     }

# @app.get("/debug")
# def debug():
#     return {
#         "model_loaded": True,
#         "labels": model.config.id2label
#     }

# @app.post("/predict")
# async def predict(request: Request):
#     data = await request.json()
#     text = data.get("inputs")
#     if not text:
#         return {"error": "Missing text"}

#     try:
#         inputs = tokenizer(text, return_tensors="pt")
#         with torch.no_grad():
#             logits = model(**inputs).logits
#         predicted_class_id = logits.argmax().item()
#         label = model.config.id2label[predicted_class_id]
#         score = torch.nn.functional.softmax(logits, dim=1).max().item()

#         return {
#             "label": label,
#             "score": score
#         }

#     except Exception as e:
#         return {
#             "error": "Prediction failed",
#             "detail": str(e)
#         }










from fastapi import FastAPI, Request
import os
from huggingface_hub import InferenceClient

app = FastAPI()

HF_MODEL_ID = "distilbert/distilbert-base-uncased-finetuned-sst-2-english"
HF_TOKEN = os.getenv("HF_TOKEN")

client = InferenceClient(
    model=HF_MODEL_ID,
    token=HF_TOKEN,
)

@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "bert",
        "huggingface_model": HF_MODEL_ID,
        "token_set": bool(HF_TOKEN)
    }

@app.get("/test-model")
def test_model():
    try:
        result = client.text_classification("You are a great doctor!")
        return {"result": result}
    except Exception as e:
        return {
            "error": "Failed to use InferenceClient",
            "detail": str(e)
        }

@app.get("/debug")
def debug():
    return {
        "hf_token_set": bool(HF_TOKEN),
        "model_id": HF_MODEL_ID
    }

@app.get("/")
def root():
    return {"message": "Sentiment model is live 🎯"}

@app.post("/predict")
async def predict(request: Request):
    data = await request.json()
    text = data.get("inputs")

    if not text:
        return {"error": "Missing text"}

    try:
        result = client.text_classification(text)
        return {"result": result}
    except Exception as e:
        return {
            "error": "Failed to get prediction",
            "detail": str(e)
        }













# from fastapi import FastAPI, Request
# import requests
# import os

# app = FastAPI()

# HF_API_URL = "https://api-inference.huggingface.co/models/distilbert-base-uncased-finetuned-sst-2-english"
# HF_TOKEN = os.getenv("HF_TOKEN")

# headers = {"Authorization": f"Bearer {HF_TOKEN}"}

# @app.get("/health")
# def health_check():
#     return {"status": "ok", "service": "bert", "huggingface_model": HF_API_URL}


# @app.get("/test-model")
# def test_model():
#     headers = {"Authorization": f"Bearer {HF_TOKEN}"}
#     hf_url = HF_API_URL

#     sample_input = {"inputs": "You are a great doctor!"}

#     try:
#         response = requests.post(hf_url, headers=headers, json=sample_input)

#         # نطبع الحالة والنص الكامل
#         print("🔍 STATUS:", response.status_code)
#         print("🔍 TEXT:", response.text)

#         # نجرب نرجع كل البيانات الخام
#         return {
#             "status_code": response.status_code,
#             "text": response.text,
#             "json": response.json() if response.headers.get('Content-Type') == 'application/json' else None
#         }

#     except Exception as e:
#         return {
#             "error": "Failed to parse Hugging Face response",
#             "detail": str(e),
#             "status_code": response.status_code,
#             "raw": response.text
#         }


# @app.get("/debug")
# def debug():
#     return {
#         "hf_token_set": bool(HF_TOKEN),
#         "endpoint": HF_API_URL
#     }
# def root():
#     return {"message": "Sentiment model is live 🎯"}

# @app.post("/predict")
# async def predict(request: Request):
#     data = await request.json()
#     text = data.get("inputs")
#     if not text:
#         return {"error": "Missing text"}

#     response = requests.post(HF_API_URL, headers=headers, json={"inputs": text})

#     # DEBUGGING:
#     print("🔍 STATUS:", response.status_code)
#     print("🔍 TEXT:", response.text)

#     try:
#         result = response.json()
#     except Exception as e:
#         return {
#             "error": "Failed to parse Hugging Face response",
#             "detail": str(e),
#             "status_code": response.status_code,
#             "raw": response.text
#         }

#     return result
