from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from transformers import AutoModelForCausalLM, AutoTokenizer
import torch
import os
from dotenv import load_dotenv
import psutil

# تحميل المتغيرات البيئية
load_dotenv()

app = FastAPI(title="Chatbot API")

# إعداد CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # في الإنتاج، حدد النطاقات المسموح بها
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

print("==== Chatbot API IS STARTING ====")

# تحميل النموذج والتوكينايزر
try:
    print("==== Loading model... ====")
    tokenizer = AutoTokenizer.from_pretrained("microsoft/DialoGPT-small")
    model = AutoModelForCausalLM.from_pretrained("microsoft/DialoGPT-small")
    model.eval()
    print("==== Model loaded! ====")
    print("==== Memory usage (MB):", psutil.virtual_memory().used / 1024 / 1024)
except Exception as e:
    print("Error loading model:", e)
    raise

class ChatInput(BaseModel):
    message: str
    chat_history: list = []

@app.get("/")
async def root():
    return {"message": "Chatbot API is running"}

@app.post("/chat")
async def chat(input_data: ChatInput):
    try:
        # تحضير النص
        chat_history = input_data.chat_history
        user_message = input_data.message
        
        # تحويل التاريخ إلى نص
        chat_history_text = " ".join(chat_history)
        
        # تحضير النص الكامل
        full_text = f"{chat_history_text} {user_message}"
        
        # ترميز النص
        inputs = tokenizer.encode(full_text + tokenizer.eos_token, return_tensors='pt')
        
        # توليد الرد
        with torch.no_grad():
            outputs = model.generate(
                inputs,
                max_length=1000,
                pad_token_id=tokenizer.eos_token_id,
                no_repeat_ngram_size=3,
                do_sample=True,
                top_k=100,
                top_p=0.7,
                temperature=0.8
            )
        
        # فك ترميز الرد
        response = tokenizer.decode(outputs[0], skip_special_tokens=True)
        
        # إزالة النص الأصلي من الرد
        response = response[len(full_text):].strip()
        
        return {
            "message": user_message,
            "response": response,
            "chat_history": chat_history + [user_message, response]
        }
    except Exception as e:
        print(f"==== ERROR IN /chat: {e} ====")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    print("==== Starting Uvicorn server... ====")
    port = int(os.environ.get("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port) 
