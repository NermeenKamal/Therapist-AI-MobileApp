
# 🧠 Therapist AI App – Laravel API Backend

## 👨‍⚕️ Graduation Project | 2024 – 2025

An AI-powered therapist backend system developed using **Laravel**, **MySQL**, **Firebase**, and external **AI models** via API integration. This backend provides a secure and scalable system for therapist-patient interaction, sentiment analysis, OCR verification, and session PDF generation.

---

## 📌 Features

- 🧠 **AI Integration**:  
  Communicates with external Python AI services (Flask APIs) for:
  - Sentiment Analysis of messages  
  - OCR (ID card text recognition)  
  - PDF report generation after chat sessions

- 🔐 **Doctor Registration & Verification**:  
  - Uploads ID card image  
  - Extracts and verifies national ID via OCR  
  - Ensures no duplicates in the database

- 💬 **Real-Time Chat** (via Firebase):  
  - Secure messaging between doctor and patient  
  - Includes support for file sharing and voice notes

- 📄 **PDF Reports**:  
  - After a chat session, the Laravel backend sends data to an AI model to generate a PDF  
  - Stored securely on the server and downloadable by patients

- 🗄️ **Database**:  
  - Built with MySQL for storing users, sessions, logs, and medical data  
  - Token-based authentication for security

---

## 🛠️ Tech Stack

| Technology     | Purpose                               |
|----------------|----------------------------------------|
| Laravel 10     | RESTful API Backend                    |
| PHP 8+         | Core Logic                             |
| MySQL          | Relational Database                    |
| Firebase       | Real-Time Chat & Voice                 |
| Railway        | API Hosting & Deployment               |
| Flask (Python) | AI Services (OCR, NLP, PDF)            |
| Postman        | API Testing & Documentation            |
| Git/GitHub     | Version Control & Code Hosting         |

---

## ⚙️ Installation

```bash
# Clone the project
git clone https://github.com/your-username/Therapist-AI-App-APIs.git

# Navigate to the project directory
cd Therapist-AI-App-APIs

# Install dependencies
composer install

# Copy and edit environment settings
cp .env.example .env

# Generate app key
php artisan key:generate

# Set up the database
php artisan migrate

# Start the server
php artisan serve
```

> Make sure you have your Firebase credentials and the AI model API URLs in your `.env` file.

---

## 🔗 API Integration Overview

```mermaid
graph LR
User --> Laravel_Backend
Laravel_Backend --> Firebase_Chat
Laravel_Backend --> MySQL_Database
Laravel_Backend --> Python_AI[Flask AI API (OCR/NLP/PDF)]
Python_AI --> Laravel_Backend
Laravel_Backend --> PDF_Storage
```

---

## 📁 Project Structure

```
├── app/
│   ├── Http/
│   ├── Models/
│   ├── Services/ (AI APIs)
├── database/
│   ├── migrations/
├── public/
├── routes/
│   ├── api.php
├── .env
├── composer.json
├── README.md
```

---

## 📄 Sample API Routes

- `POST /api/register-doctor` → Uploads ID + extracts text via OCR  
- `POST /api/start-session` → Starts chat, stores metadata  
- `GET /api/get-report/{session_id}` → Returns generated PDF  
- `POST /api/send-message` → Handles Firebase chat message

---

## 📸 Screenshots

> (اختياري – تقدر تحط هنا صور توضح الـ PDF، الـ Chat، أو ردود الـ API)

---

## 📜 License

This project is part of a university graduation requirement. For academic use only.

---

## 👨‍💻 Developed By

- **[Your Name]** – Backend Developer  
- [GitHub](https://github.com/your-username)  
- [LinkedIn](https://linkedin.com/in/your-profile)
