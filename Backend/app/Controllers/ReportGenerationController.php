<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ReportGenerationService;

class ReportGenerationController extends Controller
{
    protected ReportGenerationService $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:users,id',
            'conversation_log' => 'required|array',
        ]);

        $url = $this->reportService->generatePdf(
            $request->input('patient_id'),
            $request->input('conversation_log')
        );

        return response()->json(['report_pdf_url' => $url]);
    }
}
