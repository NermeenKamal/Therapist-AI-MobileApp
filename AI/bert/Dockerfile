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

# تحديث pip وتثبيت Torch (CPU)
RUN pip install --upgrade pip
RUN pip install torch==2.1.1 --index-url https://download.pytorch.org/whl/cpu

# تثبيت المتطلبات
RUN pip install --no-cache-dir -r requirements.txt

# تعريض المنفذ
EXPOSE 8000

# تشغيل التطبيق
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
