<?php

namespace App\Controllers;

use App\Services\OcrService;
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
            list($extractedName, $extractedId) = $this->ocrService->extractIdData($file);

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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * التحقق من البيانات المستخرجة مع البيانات المدخلة
     */
    public function verifyExtractedData(Request $request): JsonResponse
    {
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
            // التحقق من الرقم القومي
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

            return response()->json([
                'success' => true,
                'verification' => [
                    'id_verified' => $idVerified,
                    'name_verified' => $nameVerified,
                    'overall_verified' => $overallVerified
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('OCR verification failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
