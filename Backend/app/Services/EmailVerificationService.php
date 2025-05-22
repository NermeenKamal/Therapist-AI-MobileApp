<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Mail\VerificationCodeMail;

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
            
            // إرسال الإيميل
            Mail::to($email)->send(new VerificationCodeMail($code));
            
            Log::info('Verification code sent successfully', [
                'email' => $email,
                'code' => $code // يمكن إزالة هذا في البرودكشن
            ]);
            
            return true;
            
        } catch (\Exception $e) {
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
    
    /**
     * الحصول على الوقت المتبقي لانتهاء صلاحية الكود
     */
    public function getCodeExpiryTime(string $email): ?int
    {
        $key = "verification_code_{$email}";
        
        if (!Cache::has($key)) {
            return null;
        }
        
        // في Laravel، Cache::ttl() غير متاح مباشرة
        // يمكن استخدام طريقة أخرى للتتبع
        return Cache::get("{$key}_expires_at", null);
    }
}
