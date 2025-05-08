<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Services\FCMService;
use App\Models\Doctor;

class AppointmentController extends Controller
{
    public final function store(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'time' => 'required|string',
        ]);

        $appointment = Appointment::create([
            'patient_id' => Auth::id(),
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
            'time' => $request->time,
        ]);

        // إشعار للطبيب بحجز جديد
        $doctor = $appointment->doctor;
        if ($doctor && $doctor->device_token) {
            app(FCMService::class)->sendNotification(
                $doctor->device_token,
                'حجز جديد',
                "لديك حجز جديد من المريض {$appointment->patient->name} يوم {$appointment->date} الساعة {$appointment->time}"
            );
        }

        return response()->json([
            'appointment' => $appointment,
        ], 201);
    }

    public final function index(): JsonResponse
    {
        $appointments = Appointment::with(['patient', 'doctor'])->get();

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    public function confirmAppointment($appointmentId): JsonResponse
    {
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($appointmentId);
        $patient = $appointment->patient;

        $appointment->status = 'confirmed';
        $appointment->save();

        // إشعار للمريض بأن الدكتور أكد الحجز
        if ($patient && $patient->device_token) {
            app(FCMService::class)->sendNotification(
                $patient->device_token,
                'تم تأكيد الحجز',
                "الدكتور {$appointment->doctor->name} أكد موعدك في {$appointment->date} الساعة {$appointment->time}"
            );
        }

        return response()->json(['message' => 'Appointment confirmed and notification sent.']);
    }

    public function cancelAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        // تحقق إن المستخدم الحالي هو أحد أطراف الموعد
        if (Auth::id() !== $appointment->patient_id && Auth::id() !== $appointment->doctor_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // تحديد من قام بالإلغاء
        $canceller = Auth::user();
        $recipient = Auth::id() === $appointment->patient_id ? $appointment->doctor : $appointment->patient;

        // تحديث حالة الموعد
        $appointment->status = 'cancelled';
        $appointment->canceled_by = Auth::id(); // لازم يكون عندك العمود ده في جدول appointments
        $appointment->save();

        // إرسال إشعار للطرف الآخر
        if ($recipient && $recipient->device_token) {
            app(FCMService::class)->sendNotification(
                $recipient->device_token,
                'تم إلغاء الموعد',
                "{$canceller->name} ألغى الموعد بتاريخ {$appointment->date} الساعة {$appointment->time}"
            );
        }

        return response()->json(['message' => 'Appointment cancelled and notification sent.']);
    }

    public function myAppointments()
    {
        $user = Auth::user();
        $appointments = [];

        if ($user->is_doctor) {
            $appointments = Appointment::where('doctor_id', $user->id)->with('patient')->get();
        } else {
            $appointments = Appointment::where('patient_id', $user->id)->with('doctor')->get();
        }

        return response()->json(['appointments' => $appointments]);
    }



    public function getSpecializations()
    {
        $specializations = ['Cardiology', 'Dermatology', 'Neurology', 'Pediatrics', 'Psychiatry'];
        return response()->json(['specializations' => $specializations]);
    }

    public function getDoctorsBySpecialization($specialization)
    {
        $allowedSpecializations = ['Cardiology', 'Dermatology', 'Neurology', 'Pediatrics', 'Psychiatry'];

        if (!in_array($specialization, $allowedSpecializations)) {
            return response()->json(['message' => 'Invalid specialization'], 400);
        }

        $doctors = Doctor::where('specialization', $specialization)
            ->where('is_verified', true) // optional: only verified doctors
            ->with(['availableAppointments', 'bertRating']) // assuming relationships
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialization' => $doctor->specialization,
                    'bert_rating' => $doctor->bert_rating ?? 'Not rated yet',
                    'appointments' => $doctor->availableAppointments->map(function ($app) {
                        return [
                            'id' => $app->id,
                            'date' => $app->date,
                            'time' => $app->time,
                        ];
                    })
                ];
            });

        return response()->json(['doctors' => $doctors]);
    }


}
