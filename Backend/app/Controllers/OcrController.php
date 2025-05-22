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
        // التحقق من المصادقة
        if (!$request->user()) {
            Log::warning('OCR verification attempt without authentication');
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'extracted_name' => 'required|string',
            'extracted_id' => 'required|string',
            'input_name' => 'required|string',
            'input_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            
            Log::info('OCR verification started:', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_type' => get_class($user),
                'extracted_name' => $request->extracted_name,
                'extracted_id' => $request->extracted_id,
                'input_name' => $request->input_name,
                'input_id' => $request->input_id
            ]);

            // التحقق من أن المستخدم دكتور
            if (!($user instanceof Doctor)) {
                Log::warning('OCR verification attempted by non-doctor user:', [
                    'user_id' => $user->id,
                    'user_type' => get_class($user)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'OCR verification is only available for doctors'
                ], 403);
            }

            // التحقق من الهوية الوطنية
            $idVerified = $this->ocrService->verifyNationalId(
                $request->extracted_id, 
                $request->input_id
            );

            // التحقق من الاسم
            $nameVerified = $this->ocrService->verifyName(
                $request->extracted_name, 
                $request->input_name
            );

            $overallVerified = $idVerified && $nameVerified;

            Log::info('OCR verification results:', [
                'user_id' => $user->id,
                'id_verified' => $idVerified,
                'name_verified' => $nameVerified,
                'overall_verified' => $overallVerified
            ]);

            // تحديث حالة التحقق إذا نجحت العملية
            if ($overallVerified) {
                $previousStatus = $user->is_verified_by_ocr;
                
                $user->update([
                    'is_verified_by_ocr' => true,
                    'ocr_verified_at' => now()
                ]);

                // إعادة تحميل المستخدم للتأكد من التحديث
                $user->refresh();

                Log::info('Doctor OCR verification status updated:', [
                    'doctor_id' => $user->id,
                    'email' => $user->email,
                    'previous_status' => $previousStatus,
                    'new_status' => $user->is_verified_by_ocr,
                    'verified_at' => $user->ocr_verified_at
                ]);

                // التحقق من أن التحديث تم بنجاح
                if (!$user->is_verified_by_ocr) {
                    Log::error('Failed to update OCR verification status:', [
                        'doctor_id' => $user->id,
                        'email' => $user->email
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to update verification status'
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'verification' => [
                    'id_verified' => $idVerified,
                    'name_verified' => $nameVerified,
                    'overall_verified' => $overallVerified
                ],
                'user_status' => [
                    'is_verified_by_ocr' => $user->is_verified_by_ocr,
                    'email_verified' => $user->email_verified
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('OCR verification failed:', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed',
                'error' => app()->environment('local') ? $e->getMessage() : 'Processing failed'
            ], 500);
        }
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
