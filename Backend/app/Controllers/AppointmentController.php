<?php

namespace App\Controllers;

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
            'doctor_id' => 'required|exists:doctors,id',
            'doctor_schedule_id' => 'required|exists:doctor_schedules,id',
        ]);
        $data['patient_id'] = Auth::id();

        // تحقق أن الـ slot غير محجوز
        $alreadyBooked = Appointment::where('doctor_id', $data['doctor_id'])
            ->where('doctor_schedule_id', $data['doctor_schedule_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
        if ($alreadyBooked) {
            return response()->json(['message' => 'this appointment is already taken'], 422);
        }

        $appointment = Appointment::create($data);

        $doctor = $appointment->doctor;
        if ($doctor && $doctor->fcm_token) {
            $this->fcm->sendToUser(
                $doctor->fcm_token,
                'new appointment',
                "new appointment number:  " . Auth::user()->name,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment->load('doctorSchedule'), 201);
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
                'appointment canceled',
                'appointment canceled number:  ' . $appointment->id,
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
            'doctor_schedule_id' => 'required|exists:doctor_schedules,id',
        ]);

        // تحقق أن الـ slot غير محجوز (عدا هذا الموعد)
        $alreadyBooked = Appointment::where('doctor_id', $appointment->doctor_id)
            ->where('doctor_schedule_id', $data['doctor_schedule_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $appointment->id)
            ->exists();
        if ($alreadyBooked) {
            return response()->json(['message' => 'this appointment is already taken'], 422);
        }

        $appointment->update(['doctor_schedule_id' => $data['doctor_schedule_id']]);

        $other = ($appointment->patient_id === Auth::id()) ? $appointment->doctor : $appointment->patient;
        if ($other && $other->fcm_token) {
            $this->fcm->sendToUser(
                $other->fcm_token,
                'appointment edit',
                'appointment edited number: ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment->load('doctorSchedule'));
    }
}
