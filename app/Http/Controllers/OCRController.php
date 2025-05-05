<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OCRController extends Controller
{
    /**
     * استخراج الرقم القومي من الصورة والتحقق من كونه لطبيب مسجل في النظام.
     */
    public final function extractAndVerifyDoctorId(Request $request) : \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120', // حجم أقصى 5 ميجا
        ]);

        // حفظ الصورة مؤقتاً
        $path = $request->file('image')->store('ocr_temp');

        // استخدام Tesseract لاستخراج النص من الصورة
        $ocr = new TesseractOCR(storage_path("app/{$path}"));
        $text = $ocr->run();

        // استخراج الرقم القومي باستخدام Regular Expression
        preg_match('/\b[23]\d{13}\b/', $text, $matches);
        $nationalId = $matches[0] ?? null;

        // حذف الصورة بعد المعالجة
        Storage::delete($path);

        if (!$nationalId) {
            return response()->json(['error' => 'لم يتم العثور على رقم قومي صالح في الصورة'], 422);
        }

        // التحقق من أن الرقم القومي ينتمي لطبيب في قاعدة البيانات
        $doctor = User::where('national_id', $nationalId)
            ->where('role', 'doctor')
            ->first();

        if (!$doctor) {
            return response()->json([
                'verified' => false,
                'message' => 'الرقم القومي لا يعود إلى دكتور مسجل في النظام',
                'extracted_id' => $nationalId,
                'raw_text' => $text,
            ]);
        }

        // نجاح التحقق
        return response()->json([
            'verified' => true,
            'message' => 'تم التحقق من هوية الدكتور بنجاح',
            'doctor_name' => $doctor->name,
            'extracted_id' => $nationalId,
            'raw_text' => $text,
        ]);
    }

}
