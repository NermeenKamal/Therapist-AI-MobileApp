<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Doctor;
use App\Mail\VerificationCodeMail;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EmailVerificationService
{
    /**
     * إرسال كود التفعيل عبر الإيميل
     */
    public function sendVerificationCode(string $email): bool
    {
        Log::info('Starting verification code generation', ['email' => $email]);
        
        try {
            // توليد كود من 6 أرقام
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Log::info('Code generated successfully', ['email' => $email, 'code' => $code]);

            // اختبار Cache قبل الحفظ
            $testKey = "test_cache_" . time();
            Cache::put($testKey, 'test_value', 60);
            $testValue = Cache::get($testKey);
            
            if ($testValue !== 'test_value') {
                Log::error('Cache is not working properly', [
                    'email' => $email,
                    'test_key' => $testKey,
                    'expected' => 'test_value',
                    'actual' => $testValue
                ]);
                throw new Exception('Cache system is not functioning properly');
            }
            
            Cache::forget($testKey); // تنظيف
            Log::info('Cache test passed', ['email' => $email]);

            // حفظ الكود في الكاش لمدة 10 دقائق
            $cacheKey = "verification_code_{$email}";
            Cache::put($cacheKey, $code, 600);

            // التحقق من حفظ الكود في الكاش
            $savedCode = Cache::get($cacheKey);
            
            if ($savedCode !== $code) {
                Log::error('Failed to save verification code to cache', [
                    'email' => $email,
                    'generated_code' => $code,
                    'saved_code' => $savedCode,
                    'cache_key' => $cacheKey
                ]);
                throw new Exception('Failed to store verification code in cache');
            }
            
            Log::info('Verification code saved to cache successfully', [
                'email' => $email,
                'cache_key' => $cacheKey,
                'code_length' => strlen($code),
                'expires_in_seconds' => 600
            ]);

            // إرسال البريد باستخدام VerificationCodeMail class
            try {
                Log::info('Attempting to send email', ['email' => $email]);
                
                Mail::to($email)->send(new VerificationCodeMail($code));
                
                Log::info('Verification code email sent successfully', [
                    'email' => $email,
                    'mail_driver' => config('mail.default')
                ]);

            } catch (Exception $mailException) {
                Log::error('Failed to send verification code email', [
                    'email' => $email,
                    'mail_driver' => config('mail.default'),
                    'mail_host' => config('mail.mailers.smtp.host', 'N/A'),
                    'mail_port' => config('mail.mailers.smtp.port', 'N/A'),
                    'error' => $mailException->getMessage(),
                    'error_code' => $mailException->getCode(),
                    'trace' => $mailException->getTraceAsString()
                ]);
                
                // حذف الكود إذا فشل إرسال البريد
                Cache::forget($cacheKey);
                Log::info('Removed verification code from cache due to email failure', ['email' => $email]);
                
                throw $mailException;
            }

            Log::info('Verification code process completed successfully', ['email' => $email]);
            return true;

        } catch (Exception $e) {
            Log::error('Failed to send verification code - Main Exception', [
                'email' => $email,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * التحقق من كود التفعيل
     */
    public function verifyCode(string $email, string $code): bool
    {
        $cacheKey = "verification_code_{$email}";
        $cachedCode = Cache::get($cacheKey);

        // تسجيل تفاصيل محاولة التحقق
        Log::info('Verification attempt', [
            'email' => $email,
            'provided_code' => $code,
            'cached_code' => $cachedCode,
            'cache_key' => $cacheKey,
            'cache_has_key' => Cache::has($cacheKey),
            'codes_match' => $cachedCode === $code
        ]);

        if (!$cachedCode) {
            Log::warning('Verification code not found or expired', [
                'email' => $email,
                'cache_key' => $cacheKey,
                'provided_code' => $code
            ]);
            return false;
        }

        if ($cachedCode !== $code) {
            Log::warning('Invalid verification code provided', [
                'email' => $email,
                'provided_code' => $code,
                'expected_code' => $cachedCode,
                'codes_type_match' => gettype($cachedCode) === gettype($code)
            ]);
            return false;
        }

        // حذف الكود بعد التحقق الناجح
        Cache::forget($cacheKey);

        Log::info('Verification code verified successfully', [
            'email' => $email,
            'code' => $code
        ]);

        return true;
    }

    /**
     * التحقق من وجود كود غير منتهي الصلاحية
     */
    public function hasValidCode(string $email): bool
    {
        $cacheKey = "verification_code_{$email}";
        $hasCode = Cache::has($cacheKey);
        $code = Cache::get($cacheKey);
        
        Log::info('Checking for valid code', [
            'email' => $email,
            'cache_key' => $cacheKey,
            'has_code' => $hasCode,
            'cached_code' => $code
        ]);
        
        return $hasCode;
    }

    /**
     * استرجاع الكود المحفوظ (للتطوير فقط)
     */
    public function getStoredCode(string $email): ?string
    {
        $cacheKey = "verification_code_{$email}";
        $code = Cache::get($cacheKey);
        
        Log::info('Getting stored code', [
            'email' => $email,
            'cache_key' => $cacheKey,
            'stored_code' => $code
        ]);
        
        return $code;
    }

    public function sendOcrVerificationToken($email)
    {
        $doctor = Doctor::where('email', $email)->first();
        
        if (!$doctor || !$doctor->email_verified) {
            return false;
        }
        
        // إنشاء رمز تحقق جديد
        $token = Str::random(32);
        
        // تخزين الرمز في قاعدة البيانات مع وقت انتهاء الصلاحية
        DB::table('ocr_verification_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'expires_at' => now()->addHours(24)
            ]
        );
        
        // إرسال الرمز بالبريد الإلكتروني
        Mail::to($email)->send(new OcrVerificationMail($token));
        
        return true;
    }

    public function verifyOcrToken($email, $token)
    {
        $record = DB::table('ocr_verification_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
        
        return $record !== null;
    }
}
