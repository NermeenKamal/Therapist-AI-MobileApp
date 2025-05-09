<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\PatientReport;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'conversation_log' => 'required|string|min:10'
        ]);

        $response = Http::post('http://5003-izw2x95fu492p51h4uy5z-5f8e2608.manus.computer/ai/reports/generate-pdf', [
            'patient_id' => $request->patient_id,
            'conversation_log' => $request->conversation_log,
        ]);

        if (!$response->ok()) {
            return response()->json([
                'error' => $response->json()['error'] ?? 'Report generation failed.'
            ], 500);
        }

        $data = $response->json();

        $pdfContent = base64_decode($data['pdf_base64']);
        $filename = $data['filename'];
        $path = "reports/{$request->patient_id}/$filename";

        Storage::disk('public')->put($path, $pdfContent);

        // تخزين المسار في قاعدة البيانات
        $report = PatientReport::create([
            'patient_id' => $request->patient_id,
            'file_path' => $path,
            'original_filename' => $filename,
        ]);

        return response()->json([
            'message' => 'PDF report generated and stored successfully.',
            'report' => $report,
        ]);
    }


    public function download($id)
    {
        $user = Auth::user();
        $report = PatientReport::findOrFail($id);

        if ($user->role === 'patient' && $user->id != $report->patient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $path = storage_path('app/public/' . $report->file_path);
        return response()->download($path, $report->original_filename);
    }



    public function getPatientReports($id)
    {
        $user = Auth::user();

        // لو المستخدم مريض: فقط يقدر يجيب تقاريره هو
        if ($user->role === 'patient' && $user->id != $id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // لو دكتور: نتحقق لو عنده فعلاً جلسة مع هذا المريض (اختياري)

        $reports = PatientReport::where('patient_id', $id)->latest()->get();

        return response()->json([
            'reports' => $reports
        ]);
    }

}
