<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Doctor;
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
        try {
            // توليد كود من 6 أرقام
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // حفظ الكود في الكاش لمدة 10 دقائق
            Cache::put("verification_code_{$email}", $code, 600);

            // التحقق من حفظ الكود في الكاش
            $savedCode = Cache::get("verification_code_{$email}");
            Log::info('Generated and saved verification code', [
                'email' => $email,
                'code_generated' => $code,
                'code_saved' => $savedCode,
                'cache_key' => "verification_code_{$email}",
                'expires_in_seconds' => 600
            ]);

            // إرسال البريد باستخدام Mail::html()
            $emailSent = false;
            try {
                Mail::html(
                    "<h2>Verification Code</h2>" .
                    "<p>Your verification code is: <strong>" . $code . "</strong></p>" .
                    "<p>This code will expire in 10 minutes.</p>" .
                    "<p>If you did not request this code, please ignore this email.</p>",
                    function($message) use ($email) {
                        $message->to($email)
                                ->subject('Verification Code');
                    }
                );
                
                $emailSent = true;
                Log::info('Verification code email sent successfully', [
                    'email' => $email,
                    'code' => $code
                ]);

            } catch (Exception $mailException) {
                Log::error('Failed to send verification code email', [
                    'email' => $email,
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString()
                ]);
                
                // حذف الكود إذا فشل إرسال البريد
                Cache::forget("verification_code_{$email}");
                
                throw $mailException;
            }

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send verification code', [
                'email' => $email,
                'error' => $e->getMessage(),
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
