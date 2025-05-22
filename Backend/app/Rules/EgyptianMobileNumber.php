<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EgyptianMobileNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // إزالة الرموز والمسافات
        $cleanNumber = preg_replace('/[^\d]/', '', $value);

        // أنماط الموبايلات المصرية
        $patterns = [
            '/^01[0125]\d{8}$/',     // مصري عادي
            '/^00201[0125]\d{8}$/',  // مع 002
            '/^\\+201[0125]\d{8}$/'  // مع +
        ];

        $isValid = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanNumber)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $fail('Please enter a valid Egyptian mobile number.');
        }
    }
}
