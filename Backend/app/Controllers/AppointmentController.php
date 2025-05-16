<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $appointments = Appointment::where(function($q) use ($user) {
            $q->where('patient_id', $user->id)
              ->orWhere('doctor_id', $user->id);
        })->with(['doctor', 'patient'])->get();
        return response()->json($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date_time' => 'required|date',
        ]);
        $data['patient_id'] = Auth::id();
        $appointment = Appointment::create($data);

        $doctor = $appointment->doctor;
        if ($doctor->fcm_token) {
            $this->fcm->sendToUser(
                $doctor->fcm_token,
                'موعد جديد',
                "لديك موعد جديد مع " . Auth::user()->name,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment, 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('cancel', $appointment);
        $appointment->status = 'canceled';
        $appointment->canceled_by = Auth::id();
        $appointment->save();

        $other = ($appointment->patient_id === Auth::id()) ? $appointment->doctor : $appointment->patient;
        if ($other->fcm_token) {
            $this->fcm->sendToUser(
                $other->fcm_token,
                'تم إلغاء موعد',
                'تم إلغاء موعدك رقم ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment);
    }

    public function specializations(): JsonResponse
    {
        $specs = User::where('role', 'doctor')->distinct()->pluck('specialization');
        return response()->json($specs);
    }

    public function doctorsBySpecialization(string $specialization): JsonResponse
    {
        $doctors = User::where('role', 'doctor')
                        ->where('specialization', $specialization)
                        ->get();
        return response()->json($doctors);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'date_time' => 'required|date',
        ]);
        $appointment->update(['date_time' => $data['date_time']]);

        $other = ($appointment->patient_id === Auth::id()) ? $appointment->doctor : $appointment->patient;
        if ($other && $other->fcm_token) {
            $this->fcm->sendToUser(
                $other->fcm_token,
                'تعديل موعد',
                'تم تعديل موعدك رقم ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment);
    }
}
