<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\FCMService;
use App\Models\Doctor;
use App\Models\Patient;
use Exception;

class FCMController extends Controller
{
    protected FCMService $fcmService;
    
    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    
    /**
     * تحديث رمز FCM للمستخدم الحالي
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateToken(Request $request): JsonResponse
    {
        try {
            // التحقق من صحة البيانات المدخلة
            $validatedData = $request->validate([
                'fcm_token' => 'required|string',
                'user_type' => 'required|in:doctor,patient',
                'user_id' => 'required|integer'
            ]);
            
            Log::info('FCM Token Update Request', [
                'user_type' => $validatedData['user_type'],
                'user_id' => $validatedData['user_id'],
                'token_length' => strlen($validatedData['fcm_token'])
            ]);
            
            $userType = $validatedData['user_type'];
            $userId = $validatedData['user_id'];
            $fcmToken = $validatedData['fcm_token'];
            
            DB::beginTransaction();
            
            try {
                // تحديث رمز FCM حسب نوع المستخدم (دكتور أو مريض)
                if ($userType === 'doctor') {
                    $doctor = Doctor::find($userId);
                    
                    // التحقق من وجود سجل الطبيب
                    if (!$doctor) {
                        Log::warning('Doctor not found', ['doctor_id' => $userId]);
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Doctor not found',
                            'error_code' => 'DOCTOR_NOT_FOUND'
                        ], 404);
                    }
                    
                    // تسجيل الرمز القديم للمقارنة
                    $oldToken = $doctor->fcm_token;
                    
                    $doctor->fcm_token = $fcmToken;
                    $saved = $doctor->save();
                    
                    if (!$saved) {
                        Log::error('Failed to save doctor FCM token', ['doctor_id' => $userId]);
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to update FCM token',
                            'error_code' => 'UPDATE_FAILED'
                        ], 500);
                    }
                    
                    Log::info('Doctor FCM token updated', [
                        'doctor_id' => $userId,
                        'old_token' => substr($oldToken ?? '', 0, 10) . '...',
                        'new_token' => substr($fcmToken, 0, 10) . '...'
                    ]);
                    
                } else if ($userType === 'patient') {
                    $patient = Patient::find($userId);
                    
                    // التحقق من وجود سجل المريض
                    if (!$patient) {
                        Log::warning('Patient not found', ['patient_id' => $userId]);
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Patient not found',
                            'error_code' => 'PATIENT_NOT_FOUND'
                        ], 404);
                    }
                    
                    // تسجيل الرمز القديم للمقارنة
                    $oldToken = $patient->fcm_token;
                    
                    $patient->fcm_token = $fcmToken;
                    $saved = $patient->save();
                    
                    if (!$saved) {
                        Log::error('Failed to save patient FCM token', ['patient_id' => $userId]);
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to update FCM token',
                            'error_code' => 'UPDATE_FAILED'
                        ], 500);
                    }
                    
                    Log::info('Patient FCM token updated', [
                        'patient_id' => $userId,
                        'old_token' => substr($oldToken ?? '', 0, 10) . '...',
                        'new_token' => substr($fcmToken, 0, 10) . '...'
                    ]);
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'FCM token updated successfully',
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Exception during FCM token update', [
                    'exception' => $e->getMessage(),
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating FCM token',
                    'error_code' => 'INTERNAL_ERROR',
                    'error_details' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
            
        } catch (Exception $e) {
            Log::error('Exception in updateToken method', [
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'error_code' => 'VALIDATION_ERROR',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 400);
        }
    }
    
    /**
     * الاشتراك في موضوع معين
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subscribeTopic(Request $request): JsonResponse
    {
        try {
            // التحقق من صحة البيانات المدخلة
            $validatedData = $request->validate([
                'topic' => 'required|string',
                'user_type' => 'required|in:doctor,patient',
                'user_id' => 'required|integer'
            ]);
            
            Log::info('Subscribe to Topic Request', [
                'topic' => $validatedData['topic'],
                'user_type' => $validatedData['user_type'],
                'user_id' => $validatedData['user_id']
            ]);
            
            $userType = $validatedData['user_type'];
            $userId = $validatedData['user_id'];
            $topic = $validatedData['topic'];
            $fcmToken = null;
            
            // الحصول على رمز FCM للمستخدم الحالي
            if ($userType === 'doctor') {
                $doctor = Doctor::find($userId);
                if (!$doctor) {
                    Log::warning('Doctor not found for topic subscription', ['doctor_id' => $userId]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Doctor not found',
                        'error_code' => 'DOCTOR_NOT_FOUND'
                    ], 404);
                }
                $fcmToken = $doctor->fcm_token;
                
                Log::info('Retrieved doctor FCM token for topic subscription', [
                    'doctor_id' => $userId,
                    'token_exists' => !empty($fcmToken)
                ]);
                
            } else if ($userType === 'patient') {
                $patient = Patient::find($userId);
                if (!$patient) {
                    Log::warning('Patient not found for topic subscription', ['patient_id' => $userId]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient not found',
                        'error_code' => 'PATIENT_NOT_FOUND'
                    ], 404);
                }
                $fcmToken = $patient->fcm_token;
                
                Log::info('Retrieved patient FCM token for topic subscription', [
                    'patient_id' => $userId,
                    'token_exists' => !empty($fcmToken)
                ]);
            }
            
            if (empty($fcmToken)) {
                Log::warning('FCM token not found', [
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No FCM token found for the user',
                    'error_code' => 'TOKEN_NOT_FOUND'
                ], 400);
            }
            
            // الاشتراك في الموضوع
            try {
                $result = $this->fcmService->subscribeToTopic([$fcmToken], $topic);
                
                Log::info('Topic subscription result', [
                    'topic' => $topic,
                    'success' => $result['success'],
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'topic' => $topic,
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
            } catch (Exception $e) {
                Log::error('Exception during topic subscription', [
                    'exception' => $e->getMessage(),
                    'topic' => $topic,
                    'user_type' => $userType,
                    'user_id' => $userId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to subscribe to topic',
                    'error_code' => 'SUBSCRIPTION_ERROR',
                    'error_details' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
            
        } catch (Exception $e) {
            Log::error('Exception in subscribeTopic method', [
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'error_code' => 'VALIDATION_ERROR',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 400);
        }
    }
    
    /**
     * إرسال إشعار للمستخدمين (محدود للمسؤولين فقط)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendNotification(Request $request): JsonResponse
    {
        try {
            // التحقق من صحة البيانات المدخلة
            $validatedData = $request->validate([
                'topic' => 'required_without:tokens|string',
                'tokens' => 'required_without:topic|array',
                'tokens.*' => 'string',
                'title' => 'required|string',
                'body' => 'required|string',
                'data' => 'sometimes|array'
            ]);
            
            $title = $validatedData['title'];
            $body = $validatedData['body'];
            $data = $validatedData['data'] ?? [];
            
            try {
                // إرسال إشعار إما لموضوع أو لرموز محددة
                if ($request->has('topic')) {
                    $topic = $validatedData['topic'];
                    
                    Log::info('Sending notification to topic', [
                        'topic' => $topic,
                        'title' => $title
                    ]);
                    
                    $result = $this->fcmService->sendToTopic($topic, $title, $body, $data);
                    
                    Log::info('Notification to topic result', [
                        'topic' => $topic,
                        'success' => $result['success']
                    ]);
                    
                } else {
                    $tokens = $validatedData['tokens'];
                    
                    Log::info('Sending notification to multiple users', [
                        'token_count' => count($tokens),
                        'title' => $title
                    ]);
                    
                    $result = $this->fcmService->sendToMultipleUsers($tokens, $title, $body, $data);
                    
                    Log::info('Notification to users result', [
                        'token_count' => count($tokens),
                        'success' => $result['success']
                    ]);
                }
                
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'result' => $result['result'] ?? null,
                    'notification_sent_at' => now()->toIso8601String(),
                    'notification_details' => [
                        'title' => $title,
                        'body' => $body,
                        'data_included' => !empty($data)
                    ]
                ]);
                
            } catch (Exception $e) {
                Log::error('Exception during notification sending', [
                    'exception' => $e->getMessage(),
                    'title' => $title
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send notification',
                    'error_code' => 'NOTIFICATION_ERROR',
                    'error_details' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
            
        } catch (Exception $e) {
            Log::error('Exception in sendNotification method', [
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'error_code' => 'VALIDATION_ERROR',
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], 400);
        }
    }
}
