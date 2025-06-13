import os
import torch
import psutil
import warnings
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
from transformers import AutoModelForCausalLM, AutoTokenizer
from fastapi.middleware.cors import CORSMiddleware

# إعداد كاش Transformers
os.environ["HF_HOME"] = "./cache"

# تجاهل التحذيرات
warnings.filterwarnings("ignore", category=FutureWarning)

app = FastAPI()

# السماح لكل Origins - Railway frontend أو Laravel API
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# تحميل النموذج مرّة واحدة فقط
print("🚀 Loading DialoGPT model...")
tokenizer = AutoTokenizer.from_pretrained("microsoft/DialoGPT-small")
model = AutoModelForCausalLM.from_pretrained("microsoft/DialoGPT-small")
model.eval()

class ChatInput(BaseModel):
    message: str
    chat_history: Optional[List[str]] = None

@app.get("/")
def root():
    return {"message": "Chatbot is up and running 🟢"}

@app.post("/chat")
def chat(input_data: ChatInput):
    try:
        history = input_data.chat_history or []
        full_text = " ".join(history + [input_data.message])
        inputs = tokenizer.encode(full_text + tokenizer.eos_token, return_tensors='pt')

        with torch.no_grad():
            outputs = model.generate(
                inputs,
                max_length=1000,
                pad_token_id=tokenizer.eos_token_id,
                do_sample=True,
                top_k=50,
                top_p=0.95
            )

        response = tokenizer.decode(outputs[0], skip_special_tokens=True)[len(full_text):].strip()
        return {
            "message": input_data.message,
            "response": response,
            "chat_history": history + [input_data.message, response]
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
