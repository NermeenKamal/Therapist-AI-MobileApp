<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Download the AI-generated report for the authenticated patient.
     *
     * @return\Illuminate\Http\JsonResponse
     */
    public function download()
    {
        $user = Auth::user();

        if ($user->role !== 'patient') {
            return response()->json([
                'message' => 'Access denied. Only patients can download reports.'
            ], 403);
        }

        if (!$user->report_file_path || !Storage::disk('public')->exists($user->report_file_path)) {
            return response()->json([
                'message' => 'Report not found.'
            ], 404);
        }

        return response()->download(storage_path("app/public/" . $user->report_file_path));
    }
}
