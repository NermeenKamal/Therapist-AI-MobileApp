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

        public function createAvailableAppointment(Request $request): JsonResponse
    {
        $this->authorize('create', Appointment::class); // تأكد الدكتور بس هو اللي يعمل كده

        $data = $request->validate([
            'appointment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $appointment = Appointment::create([
            'doctor_id' => Auth::id(),
            'appointment_date' => $data['appointment_date'],
            'patient_id' => null,
            'status' => Appointment::STATUS_AVAILABLE,
            'notes' => $data['notes'] ?? null,
            'price' => $data['price'] ?? null,
        ]);

        if (!$appointment->id) {
            return response()->json(['message' => 'Failed to create appointment'], 500);
        }

        return response()->json($appointment, 201);
    }

    public function bookAvailableAppointment(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::where('id', $id)
            ->whereNull('patient_id')
            ->where('status', 'available')
            ->firstOrFail();

        $appointment->update([
            'patient_id' => Auth::id(),
            'status' => 'pending',
        ]);

        $doctor = $appointment->doctor;
        if ($doctor && $doctor->fcm_token) {
            $this->fcm->sendToUser(
                $doctor->fcm_token,
                'New appointment booked',
                'Appointment booked by: ' . Auth::user()->name,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment->load(['doctor']));
    }

    public function availableForDoctor(int $doctorId): JsonResponse
    {
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereNull('patient_id')
            ->where('status', 'available')
            ->get();

        return response()->json($appointments);
    }




    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Booking by schedule is disabled.'], 403);
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
            'appointment_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);
        
        $appointment->update($data);
        

        $other = ($appointment->patient_id === Auth::id()) ? $appointment->doctor : $appointment->patient;
        if ($other && $other->fcm_token) {
            $this->fcm->sendToUser(
                $other->fcm_token,
                'appointment edit',
                'appointment edited number: ' . $appointment->id,
                ['appointment_id' => $appointment->id]
            );
        }

        return response()->json($appointment);
    }
}
