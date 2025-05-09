<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OcrService;

class OcrVerificationController extends Controller
{
    protected OcrService $ocrService;

    public function __construct(OcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id_image' => 'required|image',
        ]);

        $image = $request->file('id_image');
        [$name, $id] = $this->ocrService->extractIdData($image);
        $matchStatus = $this->ocrService->verifyAgainstDatabase($id);

        return response()->json([
            'extracted_name' => $name,
            'extracted_id' => $id,
            'match_status' => $matchStatus,
        ]);
    }
}
