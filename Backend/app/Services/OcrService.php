<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class OcrService
{
    public function extractIdData(UploadedFile $image): array
    {
        $name = 'John Doe';
        $id = '12345678901234';
        return [$name, $id];
    }

    public function verifyAgainstDatabase(string $id): string
    {
        $exists = \App\Models\User::where('national_id', $id)->exists();
        return $exists ? 'matched' : 'not_matched';
    }
}
