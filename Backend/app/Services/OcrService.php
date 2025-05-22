<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    /**
     * استخراج البيانات من بطاقة الهوية
     */
    public function extractIdData(UploadedFile $file): array
    {
        try {
            // حفظ الملف مؤقتاً
            $tempPath = $file->store('temp');
            $fullPath = Storage::path($tempPath);

            // تشغيل OCR
            $ocr = new TesseractOCR($fullPath);
            $ocr->lang('ara', 'eng'); // دعم العربية والإنجليزية
            $ocr->configFile('hocr'); // تحسين الجودة
            
            $text = $ocr->run();
            
            // حذف الملف المؤقت
            Storage::delete($tempPath);
            
            Log::info('OCR Text Extracted:', ['text' => $text]);
            
            // استخراج الاسم والرقم القومي
            $extractedName = $this->extractName($text);
            $extractedId = $this->extractNationalId($text);
            
            return [$extractedName, $extractedId];
            
        } catch (\Exception $e) {
            Log::error('OCR extraction failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to extract data from image: ' . $e->getMessage());
        }
    }
    
    /**
     * استخراج الاسم من النص
     */
    private function extractName(string $text): string
    {
        $lines = explode("\n", $text);
        $name = '';
        
        // البحث عن أنماط الأسماء العربية والإنجليزية
        foreach ($lines as $line) {
            $line = trim($line);
            
            // تنظيف النص من الرموز غير المرغوبة
            $cleanLine = preg_replace('/[^\p{L}\p{N}\s]/u', '', $line);
            
            // البحث عن اسم يحتوي على أحرف عربية وإنجليزية
            if (strlen($cleanLine) > 10 && 
                (preg_match('/[\p{Arabic}]/u', $cleanLine) || 
                 preg_match('/[A-Za-z]/', $cleanLine)) &&
                !preg_match('/\d{14}/', $cleanLine)) { // ليس رقم قومي
                
                $name = $cleanLine;
                break;
            }
        }
        
        return trim($name);
    }
    
    /**
     * استخراج الرقم القومي من النص
     */
    private function extractNationalId(string $text): string
    {
        // البحث عن رقم من 14 رقم
        if (preg_match('/\b\d{14}\b/', $text, $matches)) {
            return $matches[0];
        }
        
        // البحث عن أرقام منفصلة بمسافات
        $cleanText = preg_replace('/\s+/', '', $text);
        if (preg_match('/\d{14}/', $cleanText, $matches)) {
            return $matches[0];
        }
        
        return '';
    }
    
    /**
     * التحقق من الرقم القومي
     */
    public function verifyNationalId(string $extractedId, string $inputId): bool
    {
        if (empty($extractedId) || empty($inputId)) {
            return false;
        }
        
        // تحويل الأرقام العربية إلى إنجليزية
        $extractedId = $this->convertArabicDigitsToEnglish($extractedId);
        $inputId = $this->convertArabicDigitsToEnglish($inputId);
        
        // إزالة المسافات والرموز
        $extractedId = preg_replace('/[^\d]/', '', $extractedId);
        $inputId = preg_replace('/[^\d]/', '', $inputId);
        
        Log::info('National ID Verification:', [
            'extracted' => $extractedId,
            'input' => $inputId,
            'match' => $extractedId === $inputId
        ]);
        
        return $extractedId === $inputId;
    }
    
    /**
     * التحقق من الاسم
     */
    public function verifyName(string $extractedName, string $inputName): bool
    {
        if (empty($extractedName) || empty($inputName)) {
            return false;
        }
        
        // تنظيف الأسماء
        $extractedName = $this->cleanName($extractedName);
        $inputName = $this->cleanName($inputName);
        
        // حساب نسبة التشابه
        $similarity = 0;
        similar_text($extractedName, $inputName, $similarity);
        
        Log::info('Name Verification:', [
            'extracted' => $extractedName,
            'input' => $inputName,
            'similarity' => $similarity,
            'verified' => $similarity > 60
        ]);
        
        return $similarity > 60; // نسبة تشابه أكثر من 60%
    }
    
    /**
     * تنظيف الاسم
     */
    private function cleanName(string $name): string
    {
        // إزالة الأرقام والرموز
        $name = preg_replace('/[^\p{L}\s]/u', '', $name);
        
        // إزالة المسافات الزائدة
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim(strtolower($name));
    }
    
    /**
     * تحويل الأرقام العربية إلى إنجليزية
     */
    private function convertArabicDigitsToEnglish(string $text): string
    {
        $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($arabic, $english, $text);
    }
    
    /**
     * تحسين جودة الصورة قبل OCR
     */
    private function preprocessImage(string $imagePath): string
    {
        try {
            // يمكن إضافة معالجة للصورة هنا باستخدام GD أو ImageMagick
            // مثل تحسين التباين، إزالة الضوضاء، إلخ
            
            return $imagePath;
        } catch (\Exception $e) {
            Log::warning('Image preprocessing failed:', ['error' => $e->getMessage()]);
            return $imagePath;
        }
    }
}
