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
        }
    }
}
