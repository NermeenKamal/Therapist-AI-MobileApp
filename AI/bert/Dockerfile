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

# تثبيت numpy أولاً
RUN pip install --no-cache-dir numpy==1.26.2

# تثبيت torch من الرابط الصحيح
RUN pip install --no-cache-dir torch==2.1.1+cpu -f https://download.pytorch.org/whl/torch_stable.html

# تثبيت بقية المتطلبات
RUN pip install --no-cache-dir -r requirements.txt

EXPOSE 8000

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8000"]
