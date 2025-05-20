<?php

namespace App\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\DoctorSchedule;
use App\Models\ChatRating;

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
            $path = $file->store('doctor_profiles', 's3');
            $url = \Storage::disk('s3')->url($path);
            $data['profile_image'] = $url;
        }

        $doctor->update($data);
        return response()->json(['message' => 'Profile updated successfully', 'doctor' => $doctor]);
    }

    // جلب كل الدكاترة حسب التخصص مع متوسط التقييم
    public function index(Request $request): JsonResponse
    {
        $specialty = $request->query('specialty');
        $query = Doctor::query();
        if ($specialty) {
            $query->where('specialization', $specialty);
        }
        $doctors = $query->get();
        $doctors = $doctors->map(function($doctor) {
            $avg = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'profile_image' => $doctor->profile_image,
                'specialization' => $doctor->specialization,
                'session_price' => $doctor->session_price,
                'average_rating' => $avg,
                'bio' => $doctor->bio,
            ];
        });
        return response()->json($doctors);
    }

    // جلب تفاصيل دكتور واحد
    public function show($id): JsonResponse
    {
        $doctor = Doctor::findOrFail($id);
        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)->get();
        $avg = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
        return response()->json([
            'id' => $doctor->id,
            'name' => $doctor->name,
            'profile_image' => $doctor->profile_image,
            'specialization' => $doctor->specialization,
            'session_price' => $doctor->session_price,
            'bio' => $doctor->bio,
            'schedules' => $schedules,
            'average_rating' => $avg,
        ]);
    }
} 
