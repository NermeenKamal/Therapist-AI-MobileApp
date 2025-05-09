<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\DoctorChatRating;

class SessionCompletionController extends Controller
{
    public function completeSession(Request $request)
    {
        // التحقق من البيانات
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
            'session_id' => 'required',
            'chat_text' => 'required|string',
            'image' => 'required|file|mimes:jpeg,png'
        ]);

        // ===============================
        // 🟢 الخطوة 1: OCR Verification
        // ===============================
        $ocrResponse = Http::attach(
            'image',
            fopen($request->file('image')->getRealPath(), 'r'),
            $request->file('image')->getClientOriginalName()
        )->post('http://5001-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/ocr/verify-id');

        if ($ocrResponse->failed()) {
            return response()->json([
                'error' => 'Failed to connect to OCR Service.',
                'details' => $ocrResponse->json()
            ], 500);
        }

        $ocrData = $ocrResponse->json();
        $extractedId = $ocrData['extracted_id'] ?? null;

        // تحديث بيانات الدكتور في قاعدة البيانات
        $doctor = Doctor::find($request->doctor_id);
        $doctor->national_id_extracted = $extractedId;
        $doctor->is_verified_by_ocr = ($ocrData['match_status'] === 'Valid (ID format correct)');
        $doctor->save();

        // ===============================
        // 🔵 الخطوة 2: Sentiment Analysis
        // ===============================
        $sentimentResponse = Http::post('http://5002-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/sentiment/analyze-chat', [
            'chat_text' => $request->chat_text
        ]);

        if ($sentimentResponse->failed()) {
            return response()->json([
                'error' => 'Failed to connect to Sentiment Analysis Service.',
                'details' => $sentimentResponse->json()
            ], 500);
        }

        $sentimentData = $sentimentResponse->json();
        $rating = DoctorChatRating::create([
            'doctor_id' => $request->doctor_id,
            'patient_id' => $request->patient_id,
            'session_id' => $request->session_id,
            'sentiment_score' => $sentimentData['sentiment_score'],
            'label' => $sentimentData['label']
        ]);

        // ===============================
        // 🟠 الخطوة 3: PDF Report Generation
        // ===============================
        $reportResponse = Http::post('http://5003-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/reports/generate-pdf', [
            'patient_id' => $request->patient_id,
            'conversation_log' => $request->chat_text
        ]);

        if ($reportResponse->failed()) {
            return response()->json([
                'error' => 'Failed to connect to PDF Report Generation Service.',
                'details' => $reportResponse->json()
            ], 500);
        }

        $reportData = $reportResponse->json();
        $pdfBase64 = $reportData['pdf_base64'] ?? null;
        $fileName = $reportData['filename'] ?? 'report.pdf';
        $filePath = "public/reports/patient_{$request->patient_id}/{$fileName}";

        // فك التشفير وتخزين الملف
        Storage::put($filePath, base64_decode($pdfBase64));

        // تحديث مسار التقرير في جدول المرضى
        $patient = Patient::find($request->patient_id);
        $patient->report_path = $filePath;
        $patient->save();

        // ===============================
        // ✅ إرجاع الرد النهائي
        // ===============================
        return response()->json([
            'message' => 'Session completed successfully',
            'doctor' => $doctor,
            'rating' => $rating,
            'report_link' => asset("storage/reports/patient_{$request->patient_id}/{$fileName}")
        ], 200);
    }
}
