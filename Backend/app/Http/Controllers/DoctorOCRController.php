<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class DoctorOCRController extends Controller
{
    public function verify(Request $request)
    {
        // Validate image upload
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:4096'
        ]);

        // Send image to OCR AI Service
        $response = Http::attach(
            'image',
            fopen($request->file('image')->getRealPath(), 'r'),
            $request->file('image')->getClientOriginalName()
        )->post('http://5001-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/ocr/verify-id');

        if (!$response->ok()) {
            return back()->withErrors(['ocr_error' => $response->json()['error'] ?? 'Unknown OCR Error']);
        }

        $data = $response->json();

        // Try to find the doctor by extracted national ID
        $doctor = User::where('national_id', $data['extracted_id'])->first();

        if ($doctor) {
            $doctor->update([
                'national_id_extracted' => $data['extracted_id'],
                'is_verified_by_ocr' => true,
                'ocr_debug_text' => $data['debug_raw_text'] ?? null
            ]);
        }

        return response()->json([
            'message' => $doctor ? 'Doctor verified successfully' : 'ID extracted but doctor not found in DB',
            'ocr_result' => $data,
        ]);
    }
}
