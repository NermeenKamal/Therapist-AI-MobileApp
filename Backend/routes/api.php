<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\BroadcastNotificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ArticleController;

use App\Http\Controllers\DoctorOCRController;
use App\Http\Controllers\ChatSentimentController;
use App\Http\Controllers\OcrVerificationController;
use App\Http\Controllers\SentimentAnalysisController;
use App\Http\Controllers\ReportGenerationController;

Route::post('/generate-report', [ReportGenerationController::class, 'generateReport']);

Route::post('/analyze-chat', [SentimentAnalysisController::class, 'analyzeChat']);

Route::post('/verify-doctor-id', [OcrVerificationController::class, 'verifyDoctorId']);

Route::post('/generate-report', [ReportController::class, 'generate']);

Route::post('/analyze-chat', [ChatSentimentController::class, 'analyze']);

Route::post('/doctor/verify-id', [DoctorOCRController::class, 'verify']);












use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Basic connection test
Route::get('/db-test', function () {
    try {
        $connection = DB::connection()->getPdo();
        return response()->json([
            'success' => true,
            'message' => 'Connected successfully to database: ' . DB::connection()->getDatabaseName(),
            'connection' => [
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port')
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Connection failed: ' . $e->getMessage()
        ], 500);
    }
});

// Get all tables using Laravel Schema
Route::get('/tables', function () {
    try {
        $tables = Schema::getAllTables();
        return response()->json([
            'success' => true,
            'tables' => $tables
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get tables: ' . $e->getMessage()
        ], 500);
    }
});

// Alternative way to check for tables
Route::get('/check-users-table', function () {
    try {
        $exists = Schema::hasTable('users');
        $columns = [];

        if ($exists) {
            $columns = Schema::getColumnListing('users');
        }

        return response()->json([
            'success' => true,
            'table_exists' => $exists,
            'columns' => $columns
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error checking table: ' . $e->getMessage()
        ], 500);
    }
});














/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes here are prefixed with /api automatically.
*/

// ---------------------- Public Routes ---------------------- //

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Patient Routes (Public)
Route::post('/patient/register', [PatientController::class, 'registerPatient']);
Route::post('/patient/login', [PatientController::class, 'loginPatient']);

// Doctor Routes (Public)
Route::post('/doctor/register', [DoctorController::class, 'register']);
Route::post('/doctor/login', [DoctorController::class, 'login']);






// هذا المسار مخصّص للـ AI Team (Webhook) لاستقبال الردود
Route::post('/chat/reply', [ChatController::class, 'botReply']);


// ---------------------- Protected Routes ---------------------- //

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/report/{id}/download', [ReportController::class, 'download']);
    Route::get('/patient/{id}/reports', [ReportController::class, 'getPatientReports']);

    // patient and doctor messages
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/history', [ChatController::class, 'getMessages']);


    // Appointments
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/specializations', [AppointmentController::class, 'getSpecializations']);
    Route::get('/doctors/by-specialization/{specialization}', [AppointmentController::class, 'getDoctorsBySpecialization']);


    Route::post('/send-notification', [FCMController::class, 'sendNotification']);

    // Doctor Routes
    Route::post('/doctor/upload-documents', [DoctorController::class, 'uploadDocuments']);
    Route::post('/doctor/verify/{doctorId}', [DoctorController::class, 'verifyDoctor']);
    Route::post('/doctor/update-profile', [DoctorController::class, 'updateProfile']);
    Route::get('/doctor/appointments', [DoctorController::class, 'getAppointments']);

    // Patient Routes
    Route::put('/patient/update-profile', [PatientController::class, 'updatePatientProfile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);


    Route::post('/save-token', [DeviceTokenController::class, 'store']);

    Route::post('/notify-all', [BroadcastNotificationController::class, 'sendToAll']);
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancelAppointment']);

// Get all notifications
    Route::get('/notifications', [NotificationController::class, 'index']);

// Mark one as read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // OCR Routes
    Route::post('/ocr/extract-id', [OCRController::class, 'extractNationalIdFromImage']);
    Route::post('/ocr/verify-doctor-id', [OCRController::class, 'extractAndVerifyDoctorId']);

    Route::get('/patient/report', [ReportController::class, 'download']);

    // Article Routes
    Route::get('/articles', [ArticleController::class, 'index']);

});
