FROM python:3.9-slim

WORKDIR /app

# تثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    software-properties-common \
    && rm -rf /var/lib/apt/lists/*

# نسخ ملفات المشروع
COPY requirements.txt .
COPY app.py .

# تحديث pip
RUN pip install --upgrade pip


# تثبيت باقي المتطلبات
RUN pip install --no-cache-dir -r requirements.txt

# تعريض المنفذ
EXPOSE 8000

# تشغيل التطبيق
CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
