<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Patient;

class ReportGenerationController extends Controller
{
    // دالة توليد التقرير
    public function generateReport(Request $request)
    {
        // التحقق من البيانات المطلوبة
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'conversation_log' => 'required|string'
        ]);

        // إرسال البيانات إلى خدمة الـ PDF Generation
        $response = Http::post('http://5003-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/reports/generate-pdf', [
            'patient_id' => $request->patient_id,
            'conversation_log' => $request->conversation_log
        ]);

        // التحقق إذا كان الاتصال بالخدمة نجح
        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to connect to PDF Report Generation Service.',
                'details' => $response->json()
            ], 500);
        }

        // استخراج البيانات من الـ Response
        $data = $response->json();
        $pdfBase64 = $data['pdf_base64'] ?? null;
        $fileName = $data['filename'] ?? 'report.pdf';

        // فك تشفير الـ Base64 وحفظ الـ PDF في التخزين المحلي
        $pdfData = base64_decode($pdfBase64);
        $filePath = "public/reports/patient_{$request->patient_id}/{$fileName}";

        // حفظ الملف في التخزين المحلي (Laravel Storage)
        Storage::put($filePath, $pdfData);

        // تحديث مسار التقرير في جدول المرضى
        $patient = Patient::find($request->patient_id);
        $patient->report_path = $filePath;
        $patient->save();

        // إرسال الاستجابة النهائية
        return response()->json([
            'message' => 'Report generated successfully',
            'file_path' => asset("storage/reports/patient_{$request->patient_id}/{$fileName}")
        ], 200);
    }
}
