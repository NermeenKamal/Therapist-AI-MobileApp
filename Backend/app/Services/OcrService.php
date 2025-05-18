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

            // معالجة الصورة
            $processedPath = $this->preprocessImage($fullPath);

            // استخدام Tesseract مع دعم اللغة العربية والإنجليزية
            $tesseract = new TesseractOCR($processedPath);
            $tesseract->lang('ara', 'eng');
            $ocrText = $tesseract->run();

            // تسجيل النص المستخرج
            Log::info('OCR Text extracted:', ['text' => $ocrText]);

            // تحويل الأرقام العربية إلى إنجليزية
            $normalizedText = $this->convertArabicDigitsToEnglish($ocrText);
            Log::info('Normalized Text:', ['text' => $normalizedText]);

            // استخراج الاسم والرقم القومي
            $name = $this->extractName($normalizedText);
            $id = $this->extractNationalId($normalizedText);

            // حذف الملفات المؤقتة
            unlink($fullPath);
            unlink($processedPath);

            return [$name, $id];
        } catch (\Exception $e) {
            Log::error('OCR extraction failed:', [
                'error' => $e->getMessage(),
                'file' => $image->getClientOriginalName()
            ]);
            throw $e;
        }
    }

    public function verifyNationalId(string $extractedId, string $inputId): bool
    {
        // طريقة 1: البحث العادي
        $isVerifiedByOcr1 = str_contains($extractedId, $inputId);
        Log::info('Verification Method 1 (Original):', ['result' => $isVerifiedByOcr1]);

        // طريقة 2: استخراج الأرقام فقط من كلا النصين ثم المقارنة
        $extractedDigitsOnly = preg_replace('/[^0-9]/', '', $extractedId);
        $inputDigitsOnly = preg_replace('/[^0-9]/', '', $inputId);
        $isVerifiedByOcr2 = str_contains($extractedDigitsOnly, $inputDigitsOnly);
        Log::info('Verification Method 2 (Digits Only):', [
            'extracted_digits' => $extractedDigitsOnly,
            'input_digits' => $inputDigitsOnly,
            'result' => $isVerifiedByOcr2
        ]);

        // طريقة 3: استخدام تعبير منتظم للمطابقة المرنة
        $escapedId = preg_quote($inputId, '/');
        $pattern = '/' . $escapedId . '/';
        $isVerifiedByOcr3 = preg_match($pattern, $extractedId) === 1;
        Log::info('Verification Method 3 (Regex):', ['result' => $isVerifiedByOcr3]);

        return $isVerifiedByOcr1 || $isVerifiedByOcr2 || $isVerifiedByOcr3;
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
        if (preg_match('/(الاسم|Name|اسم)\s*[:]*\s*([^\n]+)/ui', $ocrText, $matches)) {
            return trim($matches[2]);
        }
        return '';
    }

    private function extractNationalId(string $ocrText): string
    {
        if (preg_match('/\b\d{14}\b/', $ocrText, $matches)) {
            return $matches[0];
        }

        $textDigitsOnly = preg_replace('/[^0-9]/', '', $ocrText);
        if (preg_match('/\d{14}/', $textDigitsOnly, $matches)) {
            return $matches[0];
        }

        return '';
    }

    private function convertArabicDigitsToEnglish(string $text): string
    {
        $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($arabic, $english, $text);
    }


    private function preprocessImage($imagePath)
    {
        $info = pathinfo($imagePath);
        $processedPath = $info['dirname'] . '/' . $info['filename'] . '_processed.' . $info['extension'];

        exec("which convert", $output, $returnVar);
        if ($returnVar === 0) {
            // تحسين الصورة باستخدام ImageMagick
            $cmd = "convert " . escapeshellarg($imagePath) .
                   " -resize 300% -type Grayscale -sharpen 0x1 -contrast -normalize " .
                   escapeshellarg($processedPath);
            exec($cmd);
            return $processedPath;
        }

        // fallback في حالة عدم توفر ImageMagick
        return $imagePath;
    }

}








// <!-- <?php

// namespace App\Services;

// use Illuminate\Http\UploadedFile;
// use thiagoalessio\TesseractOCR\TesseractOCR;
// use Illuminate\Support\Facades\Log;

// class OcrService
// {
//     public function extractIdData(UploadedFile $image): array
//     {
//         try {
//             // حفظ الصورة مؤقتاً
//             $path = $image->store('temp', 'public');
//             $fullPath = storage_path('app/public/' . $path);

//             // معالجة الصورة
//             $processedPath = $this->preprocessImage($fullPath);

//             // استخدام Tesseract مع دعم اللغة العربية والإنجليزية
//             $tesseract = new TesseractOCR($processedPath);
//             $tesseract->lang('ara', 'eng');
//             $ocrText = $tesseract->run();

//             // تسجيل النص المستخرج
//             Log::info('OCR Text extracted:', ['text' => $ocrText]);

//             // تحويل الأرقام العربية إلى إنجليزية
//             $normalizedText = $this->convertArabicDigitsToEnglish($ocrText);
//             Log::info('Normalized Text:', ['text' => $normalizedText]);

//             // استخراج الاسم والرقم القومي
//             $name = $this->extractName($normalizedText);
//             $id = $this->extractNationalId($normalizedText);

//             // حذف الملفات المؤقتة
//             unlink($fullPath);
//             unlink($processedPath);

//             return [$name, $id];
//         } catch (\Exception $e) {
//             Log::error('OCR extraction failed:', [
//                 'error' => $e->getMessage(),
//                 'file' => $image->getClientOriginalName()
//             ]);
//             throw $e;
//         }
//     }

//     public function verifyNationalId(string $extractedId, string $inputId): bool
//     {
//         // طريقة 1: البحث العادي
//         $isVerifiedByOcr1 = str_contains($extractedId, $inputId);
//         Log::info('Verification Method 1 (Original):', ['result' => $isVerifiedByOcr1]);

//         // طريقة 2: استخراج الأرقام فقط من كلا النصين ثم المقارنة
//         $extractedDigitsOnly = preg_replace('/[^0-9]/', '', $extractedId);
//         $inputDigitsOnly = preg_replace('/[^0-9]/', '', $inputId);
//         $isVerifiedByOcr2 = str_contains($extractedDigitsOnly, $inputDigitsOnly);
//         Log::info('Verification Method 2 (Digits Only):', [
//             'extracted_digits' => $extractedDigitsOnly,
//             'input_digits' => $inputDigitsOnly,
//             'result' => $isVerifiedByOcr2
//         ]);

//         // طريقة 3: استخدام تعبير منتظم للمطابقة المرنة
//         $escapedId = preg_quote($inputId, '/');
//         $pattern = '/' . $escapedId . '/';
//         $isVerifiedByOcr3 = preg_match($pattern, $extractedId) === 1;
//         Log::info('Verification Method 3 (Regex):', ['result' => $isVerifiedByOcr3]);

//         return $isVerifiedByOcr1 || $isVerifiedByOcr2 || $isVerifiedByOcr3;
//     }

//     public function verifyAgainstDatabase(string $id): string
//     {
//         try {
//             $exists = \App\Models\Doctor::where('national_id', $id)->exists();
//             return $exists ? 'matched' : 'not_matched';
//         } catch (\Exception $e) {
//             Log::error('Database verification failed:', [
//                 'error' => $e->getMessage(),
//                 'national_id' => $id
//             ]);
//             throw $e;
//         }
//     }

//     private function extractName(string $ocrText): string
//     {
//         if (preg_match('/(الاسم|Name|اسم)\s*[:]*\s*([^\n]+)/ui', $ocrText, $matches)) {
//             return trim($matches[2]);
//         }
//         return '';
//     }

//     private function extractNationalId(string $ocrText): string
//     {
//         if (preg_match('/\b\d{14}\b/', $ocrText, $matches)) {
//             return $matches[0];
//         }

//         $textDigitsOnly = preg_replace('/[^0-9]/', '', $ocrText);
//         if (preg_match('/\d{14}/', $textDigitsOnly, $matches)) {
//             return $matches[0];
//         }

//         return '';
//     }

//     private function convertArabicDigitsToEnglish(string $text): string
//     {
//         $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
//         $english = ['0','1','2','3','4','5','6','7','8','9'];
//         return str_replace($arabic, $english, $text);
//     }

//     private function preprocessImage($imagePath)
//     {
//         $processedPath = '/tmp/processed_' . basename($imagePath);

//         // معالجة محسنة: تكبير، تباين، حدة، تحويل لأبيض وأسود
//         $cmd = "convert $imagePath -resize 300% -colorspace Gray -normalize -contrast -sharpen 0x2 -threshold 50% $processedPath";
//         exec($cmd, $output, $returnVar);

//         // تحقق من نجاح التحويل
//         if ($returnVar !== 0 || !file_exists($processedPath)) {
//             throw new \Exception("Image preprocessing failed: $cmd");
//         }

//         return $processedPath;
//     }

// } -->
