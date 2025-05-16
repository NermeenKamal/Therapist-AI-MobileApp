<?php

namespace App\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    // تعديل بيانات البروفايل للدكتور الحالي
    public function updateProfile(Request $request): JsonResponse
    {
        $doctor = Auth::user();
        if (!$doctor || !$doctor instanceof Doctor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'bio' => 'nullable|string',
            'session_price' => 'nullable|numeric|min:0',
            'profile_image' => 'nullable|image|max:4096', // 4MB
        ]);

        // رفع صورة جديدة لو موجودة
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $path = $file->store('doctor_profiles', 'public');
            $data['profile_image'] = $path;
        }

        $doctor->update($data);
        return response()->json(['message' => 'تم تحديث البيانات بنجاح', 'doctor' => $doctor]);
    }
} 