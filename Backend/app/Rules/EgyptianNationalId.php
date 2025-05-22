<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EgyptianNationalId implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // تأكد أنه مكون من 14 رقم
        if (!preg_match('/^\d{14}$/', $value)) {
            $fail('The national ID must be exactly 14 digits.');
            return;
        }

        // استخرج تاريخ الميلاد
        $centuryCode = substr($value, 0, 1);
        $year = substr($value, 1, 2);
        $month = substr($value, 3, 2);
        $day = substr($value, 5, 2);

        // تحديد القرن
        if ($centuryCode == '2') {
            $fullYear = '19' . $year;
        } elseif ($centuryCode == '3') {
            $fullYear = '20' . $year;
        } else {
            $fail('Invalid century code in national ID.');
            return;
        }

        // التحقق من صحة تاريخ الميلاد
        if (!checkdate((int) $month, (int) $day, (int) $fullYear)) {
            $fail('The national ID contains an invalid birth date.');
        }
    }
}
