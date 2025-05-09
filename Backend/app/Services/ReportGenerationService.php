<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ReportGenerationService
{
    public function generatePdf(int $patientId, array $conversationLog): string
    {
        $fileName = "report_{$patientId}_" . time() . ".pdf";
        $filePath = "reports/{$fileName}";
        $pdfContent = '%PDF-1.4...';
        Storage::put($filePath, $pdfContent);
        return Storage::url($filePath);
    }
}
