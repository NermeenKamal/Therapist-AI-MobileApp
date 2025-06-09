FROM python:3.9-slim

WORKDIR /app

# تثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# نسخ الملفات
COPY requirements.txt .
COPY app.py .

# تثبيت مكتبة torch + باقي المتطلبات
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu
RUN pip install --no-cache-dir -r requirements.txt

# تعيين كاش huggingface داخل الحاوية
ENV HF_HOME=/app/.cache/huggingface

# تعريض المنفذ
EXPOSE 8000

# تشغيل التطبيق
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
