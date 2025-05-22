<?php

namespace App\Controllers;

use App\Services\OcrService;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OcrController extends Controller
{
    protected $ocrService;

    public function __construct(OcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * استخراج البيانات من الهوية باستخدام OCR
     */
    public function extractIdData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_image' => 'required|file|mimes:jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('id_image');
            
            Log::info('OCR extraction started:', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            list($extractedName, $extractedId) = $this->ocrService->extractIdData($file);

            Log::info('OCR extraction successful:', [
                'extracted_name' => $extractedName,
                'extracted_id' => $extractedId
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $extractedName,
                    'national_id' => $extractedId
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('OCR extraction failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extract data from image',
                'error' => app()->environment('local') ? $e->getMessage() : 'Processing failed'
            ], 500);
        }
    }

    /**
     * التحقق من البيانات المستخرجة مع البيانات المدخلة
     * هذا الـ method يجب أن يكون محمي بـ auth middleware
     */
    public function verifyExtractedData(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'extracted_name' => 'required|string',
        'extracted_id' => 'required|string',
        'input_name' => 'required|string',
        'input_id' => 'required|string',
        'email' => 'required|email'
        // حذف متطلب verification_token
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // البحث عن الدكتور بالبريد الإلكتروني
        $doctor = Doctor::where('email', $request->email)->first();
        
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found'
            ], 404);
        }
        
        // التحقق من أن البريد الإلكتروني مفعل
        if (!$doctor->email_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email first'
            ], 403);
        }

        // باقي منطق التحقق من OCR كما هو
        // ...
    }
    // ...
}

    /**
     * التحقق من حالة التحقق الحالية للمستخدم
     */
    public function getVerificationStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            if (!($user instanceof Doctor)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only doctors need OCR verification'
                ], 403);
            }

            Log::info('Verification status check:', [
                'doctor_id' => $user->id,
                'email' => $user->email,
                'email_verified' => $user->email_verified,
                'is_verified_by_ocr' => $user->is_verified_by_ocr,
                'ocr_verified_at' => $user->ocr_verified_at
            ]);

            return response()->json([
                'success' => true,
                'status' => [
                    'email_verified' => $user->email_verified,
                    'is_verified_by_ocr' => $user->is_verified_by_ocr,
                    'ocr_verified_at' => $user->ocr_verified_at,
                    'can_login' => $user->email_verified && $user->is_verified_by_ocr
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get verification status failed:', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get verification status'
            ], 500);
        }
    }
}
