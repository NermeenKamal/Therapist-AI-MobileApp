<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

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

            Log::info('Generated verification code for email: ' . $email);

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
                Log::info('Verification code email sent successfully to: ' . $email);

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
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * التحقق من كود التفعيل
     */
    public function verifyCode(string $email, string $code): bool
    {
        $cachedCode = Cache::get("verification_code_{$email}");

        if (!$cachedCode) {
            Log::warning('Verification code not found or expired', [
                'email' => $email
            ]);
            return false;
        }

        if ($cachedCode !== $code) {
            Log::warning('Invalid verification code provided', [
                'email' => $email,
                'provided_code' => $code
            ]);
            return false;
        }

        // حذف الكود بعد التحقق الناجح
        Cache::forget("verification_code_{$email}");

        Log::info('Verification code verified successfully', [
            'email' => $email
        ]);

        return true;
    }

    /**
     * التحقق من وجود كود غير منتهي الصلاحية
     */
    public function hasValidCode(string $email): bool
    {
        return Cache::has("verification_code_{$email}");
    }
}
