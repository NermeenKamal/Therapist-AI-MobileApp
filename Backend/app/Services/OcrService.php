<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;

class OcrService
{
    public function extractIdData(UploadedFile $image): array
    {
        try {
            // حفظ الصورة مؤقتاً
            $path = $image->store('temp', 'public');
            $fullPath = storage_path('app/public/' . $path);

            // استخدام Tesseract مع دعم اللغة الإنجليزية فقط
            $tesseract = new TesseractOCR($fullPath);
            $tesseract->lang('eng'); // تحديد اللغة الإنجليزية فقط
            $ocrText = $tesseract->run();

            // تسجيل النص المستخرج للتأكد من عمله
            Log::info('OCR Text extracted:', ['text' => $ocrText]);

            // استخراج الاسم والرقم القومي من النص
            $name = $this->extractName($ocrText);
            $id = $this->extractNationalId($ocrText);

            // حذف الملف المؤقت
            unlink($fullPath);

            return [$name, $id];
        } catch (\Exception $e) {
            Log::error('OCR extraction failed:', [
                'error' => $e->getMessage(),
                'file' => $image->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    public function verifyAgainstDatabase(string $id): string
    {
        try {
            $exists = \App\Models\Doctor::where('national_id', $id)->exists();
            return $exists ? 'matched' : 'not_matched';
        } catch (\Exception $e) {
            Log::error('Database verification failed:', [
                'error' => $e->getMessage(),
                'national_id' => $id
            ]);
            throw $e;
        }
    }

    private function extractName(string $ocrText): string
    {
       
        if (preg_match('/الاسم\s*:\s*([^\n]+)/u', $ocrText, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    private function extractNationalId(string $ocrText): string
    {
        // البحث عن الرقم القومي (14 رقم)
        if (preg_match('/\b\d{14}\b/', $ocrText, $matches)) {
            return $matches[0];
        }
        return '';
    }
}
