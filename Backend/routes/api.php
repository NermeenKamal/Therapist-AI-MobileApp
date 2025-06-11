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
use App\Controllers\ChatGPTController;
use App\Controllers\DoctorScheduleController;
use App\Controllers\DoctorController;
use App\Controllers\ArticleController;
use App\Controllers\FCMController;
use App\Controllers\PatientController;

Route::middleware('auth:sanctum')->get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

Route::post('chat/send-message', [ChatGPTController::class,'sendMessage']);
Route::post('chat/get-messages', [ChatGPTController::class,'getMessages']);
Route::post('chat/generate-report', [ChatGPTController::class,'generateReport']);

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'app_name' => config('app.name'),
        'version' => '1.0.0'
    ]);
});

// Reset password routes
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

// Public Doctor Routes (for browsing without authentication)
Route::prefix('public')->group(function () {
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::get('/doctors/{id}', [DoctorController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/specializations', [AppointmentController::class, 'specializations']);
    Route::get('/articles', [ArticleController::class, 'index']);
});


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
    
    // Chat - Enhanced messaging system with Firebase
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
    Route::post('notifications/send', [NotificationController::class, 'sendNotification']);

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

    // Protected Doctor Routes (require authentication)
    Route::post('/doctor/update-profile', [DoctorController::class, 'updateProfile']);
    Route::get('/doctors', [DoctorController::class, 'index']);
    // Route::get('/doctors/{id}', [DoctorController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/doctor-profile/{id}', [DoctorController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('doctor.profile');

    // Article Routes
    Route::get('/articles', [ArticleController::class, 'index']);
});

// Development/Testing Routes
if (app()->environment(['local', 'staging'])) {
    Route::prefix('debug')->group(function () {
        // Test email sending
        Route::get('test-email/{email}', function ($email) {
            $emailService = new \App\Services\EmailVerificationService();
            return $emailService->sendVerificationCode($email) ? 'Email sent!' : 'Failed to send email';
        });
        
        // Check user status by email
        Route::get('user/{email}', function ($email) {
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

        // Test Cloudinary configuration
        Route::get('cloudinary-config', [DoctorController::class, 'testCloudinaryConfig']);
        
        // Debug doctor data
        Route::get('doctor-debug/{id?}', [DoctorController::class, 'debug']);
        
        // Test database connection
        Route::get('db-test', function () {
            try {
                $doctorCount = \App\Models\Doctor::count();
                $patientCount = \App\Models\Patient::count();
                return response()->json([
                    'status' => 'success',
                    'database_connected' => true,
                    'doctors_count' => $doctorCount,
                    'patients_count' => $patientCount
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'database_connected' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    });
}
