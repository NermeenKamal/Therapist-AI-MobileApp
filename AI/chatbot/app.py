import gradio as gr
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM

model_id = "Nermeenkamal888/Therapy-T5-Small-Fine-Tuned-Chatbot"
tokenizer = AutoTokenizer.from_pretrained(model_id)
model = AutoModelForSeq2SeqLM.from_pretrained(model_id)

def chat(message):
    inputs = tokenizer.encode(message, return_tensors="pt")
    outputs = model.generate(inputs, max_new_tokens=100)
    response = tokenizer.decode(outputs[0], skip_special_tokens=True)
    return response

demo = gr.Interface(fn=chat, inputs="text", outputs="text", title="🧠 Therapy T5 Chatbot")

demo.launch()
