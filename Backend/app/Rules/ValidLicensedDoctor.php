<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\LicensedDoctor;

class ValidLicensedDoctor implements ValidationRule
{
    private $email;
    private $nationalId;
    private $specialization;

    public function __construct($email, $nationalId, $specialization)
    {
        $this->email = $email;
        $this->nationalId = $nationalId;
        $this->specialization = $specialization;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $licensedDoctor = LicensedDoctor::where('email', $this->email)
                                       ->where('license_number', $value)
                                       ->where('national_id', $this->nationalId)
                                       ->where('specialization', $this->specialization)
                                       ->first();

        if (!$licensedDoctor) {
            $fail('The provided doctor information does not match our licensed doctors database.');
            return;
        }

        if (!$licensedDoctor->verified) {
            $fail('Your license is pending verification by the Ministry of Health.');
            return;
        }
    }
}

// app/Rules/EgyptianNationalId.php
class EgyptianNationalId implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // التحقق من أن الرقم يحتوي على 14 رقم
        if (!preg_match('/^\d{14}$/', $value)) {
            $fail('The national ID must be exactly 14 digits.');
            return;
        }

        // التحقق من صحة تاريخ الميلاد في الرقم القومي
        $year = substr($value, 1, 2);
        $month = substr($value, 3, 2);
        $day = substr($value, 5, 2);

        // تحديد القرن
        $century = (substr($value, 0, 1) == '2' || substr($value, 0, 1) == '3') ? '19' : '20';
        $fullYear = $century . $year;

        if (!checkdate($month, $day, $fullYear)) {
            $fail('The national ID contains an invalid birth date.');
            return;
        }
    }
}

// app/Rules/EgyptianMobileNumber.php
class EgyptianMobileNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // إزالة المسافات والرموز
        $cleanNumber = preg_replace('/[^\d]/', '', $value);
        
        // التحقق من أنماط الأرقام المصرية
        $patterns = [
            '/^01[0125]\d{8}$/',  // أرقام الموبايل المصرية
            '/^2001[0125]\d{8}$/' // مع كود الدولة
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
