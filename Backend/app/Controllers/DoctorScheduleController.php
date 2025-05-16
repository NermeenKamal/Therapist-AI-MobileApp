<?php

namespace App\Controllers;

use App\Models\DoctorSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class DoctorScheduleController extends Controller
{
    // عرض كل المواعيد للدكتور الحالي
    public function index(): JsonResponse
    {
        $doctorId = Auth::user()->doctor->id;
        $schedules = DoctorSchedule::where('doctor_id', $doctorId)->get();
        return response()->json($schedules);
    }

    // إضافة معاد جديد
    public function store(Request $request): JsonResponse
    {
        $doctorId = Auth::user()->doctor->id;
        $data = $request->validate([
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        $data['doctor_id'] = $doctorId;
        $schedule = DoctorSchedule::create($data);
        return response()->json($schedule, 201);
    }

    // تعديل معاد
    public function update(Request $request, $id): JsonResponse
    {
        $doctorId = Auth::user()->doctor->id;
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)->findOrFail($id);
        $data = $request->validate([
            'day_of_week' => 'sometimes|string',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
        ]);
        $schedule->update($data);
        return response()->json($schedule);
    }

    // حذف معاد
    public function destroy($id): JsonResponse
    {
        $doctorId = Auth::user()->doctor->id;
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)->findOrFail($id);
        $schedule->delete();
        return response()->json(['message' => 'تم حذف المعاد بنجاح']);
    }
} 