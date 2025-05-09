<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Doctor;

class OcrVerificationController extends Controller
{
    // دالة التحقق من البطاقة باستخدام OCR
    public function verifyDoctorId(Request $request)
    {
        // التحقق من وجود الصورة في الطلب
        if (!$request->hasFile('image')) {
            return response()->json([
                'error' => 'Image file is required.'
            ], 400);
        }

        // تجهيز الصورة للإرسال
        $response = Http::attach(
            'image',
            fopen($request->file('image')->getRealPath(), 'r'),
            $request->file('image')->getClientOriginalName()
        )->post('http://5001-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/ocr/verify-id');

        // جلب الرد من الـ API
        $data = $response->json();

        // التحقق إذا كان في خطأ من السيرفر
        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to connect to OCR Service.',
                'details' => $data
            ], 500);
        }

        // استخراج البيانات من الـ JSON
        $extractedId = $data['extracted_id'] ?? null;
        $matchStatus = $data['match_status'] ?? 'Invalid';
        $extractedName = $data['extracted_name'] ?? 'Unknown';

        // التحقق إذا كان الرقم القومي موجود في قاعدة البيانات
        $doctor = Doctor::where('national_id', $extractedId)->first();

        if ($doctor) {
            // تحديث البيانات في قاعدة البيانات
            $doctor->national_id_extracted = $extractedId;
            $doctor->is_verified_by_ocr = $matchStatus === 'Valid (ID format correct)';
            $doctor->save();

            return response()->json([
                'message' => 'OCR Verification Successful',
                'doctor' => $doctor,
                'ocr_data' => $data
            ], 200);
        } else {
            return response()->json([
                'message' => 'Doctor not found in database.',
                'ocr_data' => $data
            ], 404);
        }
    }
}
