<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\AuthController;
use App\Controllers\AppointmentController;
use App\Controllers\ChatController;
use App\Controllers\NotificationController;
use App\Controllers\BroadcastNotificationController;
use App\Controllers\OcrVerificationController;
use App\Controllers\OcrController;
use App\Controllers\SentimentAnalysisController;
use App\Controllers\ReportGenerationController;
use App\Controllers\ForgotPasswordController;
use App\Controllers\AIChatbotController;
use App\Controllers\DoctorScheduleController;
use App\Controllers\DoctorController;
use App\Controllers\ArticleController;
use App\Controllers\FCMController;
use App\Controllers\PatientController;

// reset password routes
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');

// Public Auth Routes
Route::post('auth/register-patient', [AuthController::class, 'registerPatient']);
Route::post('auth/register-doctor', [AuthController::class, 'registerDoctor']);
Route::post('auth/login', [AuthController::class, 'login'])->name('login');

// Email Verification Routes (Public)
Route::post('auth/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('auth/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
Route::post('auth/request-ocr-token', [AuthController::class, 'requestOcrVerificationToken']);

// OCR Routes - extract data only (Public for registration flow)
Route::post('ocr/extract-id-data', [OcrController::class, 'extractIdData']);

Route::post('ocr/verify-extracted-data', [OcrController::class, 'verifyExtractedData']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth Routes
    Route::get('/check-access', [AuthController::class, 'checkAccess']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // OCR Routes (Protected - requires authentication)
    Route::get('ocr/verification-status', [OcrController::class, 'getVerificationStatus']);

    // Appointment routes
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::get('specializations', [AppointmentController::class, 'specializations']);
    Route::get('doctors/{specialization}', [AppointmentController::class, 'doctorsBySpecialization']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::post('/appointments/available', [AppointmentController::class, 'createAvailableAppointment']);
    Route::post('/appointments/book/{id}', [AppointmentController::class, 'bookAvailableAppointment']);
    Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::get('/appointments/doctor/{doctorId}/available', [AppointmentController::class, 'availableForDoctor']);

    // Patient Routes
    Route::post('/patient/profile', [PatientController::class, 'updateProfile']);
    Route::get('/patient/profile', [PatientController::class, 'showProfile']);
    
    // Chat - نظام المحادثات المُحسَّن مع Firebase
    Route::post('chat/send', [ChatController::class, 'sendMessage']);
    Route::get('chat/appointment/{appointmentId}', [ChatController::class, 'getMessages']);
    Route::post('chat/read', [ChatController::class, 'markAsRead']);
    Route::get('chat/recent', [ChatController::class, 'getRecentChats']);
    
    // Firebase Cloud Messaging (FCM)
    Route::post('fcm/update-token', [FCMController::class, 'updateToken']);
    Route::post('fcm/subscribe-topic', [FCMController::class, 'subscribeTopic']);
    Route::post('fcm/send-notification', [FCMController::class, 'sendNotification'])->middleware('role:admin');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/broadcast', [BroadcastNotificationController::class, 'broadcast']);

    // OCR, Sentiment, Reports
    Route::post('verify-doctor-id', [OcrVerificationController::class, 'verify']);
    Route::post('analyze-chat', [SentimentAnalysisController::class, 'analyze']);
    Route::post('generate-report', [ReportGenerationController::class, 'generate']);

    // AI Chatbot
    Route::post('chat/bot/message', [AIChatbotController::class, 'sendMessage']);
    Route::get('chat/bot/response/{conversationId}', [AIChatbotController::class, 'getResponse']);

    // Doctor Schedule
    Route::get('/doctor/schedule', [DoctorScheduleController::class, 'index']);
    Route::post('/doctor/schedule', [DoctorScheduleController::class, 'store']);
    Route::put('/doctor/schedule/{id}', [DoctorScheduleController::class, 'update']);
    Route::delete('/doctor/schedule/{id}', [DoctorScheduleController::class, 'destroy']);

    // Doctor Routes
    Route::post('/doctor/update-profile', [DoctorController::class, 'updateProfile']);
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show']);

    // Article Routes
    Route::get('/articles', [ArticleController::class, 'index']);
});

// Debug Routes (remove in production)
if (app()->environment('local')) {
    Route::get('test-email/{email}', function ($email) {
        $emailService = new \App\Services\EmailVerificationService();
        return $emailService->sendVerificationCode($email) ? 'Email sent!' : 'Failed to send email';
    });
    
    // Debug route للتحقق من حالة المستخدم
    Route::get('debug/user/{email}', function ($email) {
        $doctor = \App\Models\Doctor::where('email', $email)->first();
        if ($doctor) {
            return response()->json([
                'type' => 'doctor',
                'email_verified' => $doctor->email_verified,
                'is_verified_by_ocr' => $doctor->is_verified_by_ocr,
                'email_verified_at' => $doctor->email_verified_at,
                'ocr_verified_at' => $doctor->ocr_verified_at ?? 'Not set'
            ]);
        }
        
        $patient = \App\Models\Patient::where('email', $email)->first();
        if ($patient) {
            return response()->json([
                'type' => 'patient',
                'email_verified' => $patient->email_verified,
                'email_verified_at' => $patient->email_verified_at
            ]);
        }
        
        return response()->json(['message' => 'User not found'], 404);
    });
}
