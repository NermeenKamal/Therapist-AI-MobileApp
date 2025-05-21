<?php

namespace App\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    // جلب كل مواعيد المستخدم (الدكتور أو المريض)
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $appointments = Appointment::where(function($q) use ($user) {
            $q->where('patient_id', $user->id)
              ->orWhere('doctor_id', $user->id);
        })->with(['doctor', 'patient'])->get();

        return response()->json($appointments);
    }

    // الدكتور ينشئ موعد متاح
    public function createAvailableAppointment(Request $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'appointment_date' => 'required|date',
            'notes'            => 'nullable|string',
            'price'            => 'nullable|numeric',
        ]);

        $appointment = Appointment::create([
            'doctor_id'        => Auth::id(),
            'appointment_date' => $data['appointment_date'],
            'status'           => Appointment::STATUS_AVAILABLE,
            'notes'            => $data['notes']  ?? null,
            'price'            => $data['price']  ?? null,
        ]);

        return response()->json($appointment, 201);
    }

    // عرض المواعيد المتاحة لدكتور معيّن
    public function availableForDoctor(int $doctorId): JsonResponse
    {
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereNull('patient_id')
            ->where('status', Appointment::STATUS_AVAILABLE)
            ->get();

        return response()->json($appointments);
    }

    // المريض يحجز موعد جاهز
    public function bookAvailableAppointment(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::where('id', $id)
            ->whereNull('patient_id')
            ->where('status', Appointment::STATUS_AVAILABLE)
            ->firstOrFail();

        $appointment->update([
            'patient_id' => Auth::id(),
            'status'     => Appointment::STATUS_PENDING,
        ]);

        // إشعار للدكتور - مع حماية من أخطاء Firebase
        try {
            if ($appointment->doctor && $appointment->doctor->fcm_token) {
                $this->fcm->sendToUser(
                    $appointment->doctor->fcm_token,
                    'New appointment booked',
                    'Appointment booked by: ' . Auth::user()->name,
                    ['appointment_id' => $appointment->id]
                );
            }
        } catch (\Exception $e) {
            // تسجيل الخطأ بدون إيقاف العملية
            Log::warning('FCM notification failed in bookAvailableAppointment: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => Auth::id()
            ]);
        }

        return response()->json($appointment->load('doctor'));
    }

    // الدكتور يؤكد الحجز
public function confirm(Request $request, int $id): JsonResponse
{
    $appointment = Appointment::findOrFail($id);
    $this->authorize('confirm', $appointment);

    if ($appointment->status !== Appointment::STATUS_PENDING) {
        return response()->json(['message' => 'Only pending appointments can be confirmed.'], 400);
    }

    $appointment->update([
        'status' => Appointment::STATUS_BOOKED,
    ]);

    // إشعار للمريض
    try {
        if ($appointment->patient && $appointment->patient->fcm_token) {
            $this->fcm->sendToUser(
                $appointment->patient->fcm_token,
                'Appointment confirmed',
                'Your appointment has been confirmed by the doctor.',
                ['appointment_id' => $appointment->id]
            );
        }
    } catch (\Exception $e) {
        Log::warning('FCM notification failed in confirm: ' . $e->getMessage(), [
            'appointment_id' => $appointment->id,
            'user_id' => Auth::id()
        ]);
    }

    return response()->json($appointment);
}


    // تعديل موعد (تاريخ/ملاحظات)
    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'appointment_date' => 'sometimes|date',
            'notes'            => 'nullable|string',
            'price'            => 'nullable|numeric',
        ]);

        $appointment->update($data);

        // إشعار للطرف الآخر - مع حماية من أخطاء Firebase
        try {
            $other = $appointment->patient_id === Auth::id()
                ? $appointment->doctor
                : $appointment->patient;

            if ($other && $other->fcm_token) {
                $this->fcm->sendToUser(
                    $other->fcm_token,
                    'Appointment edited',
                    'Appointment edited number: ' . $appointment->id,
                    ['appointment_id' => $appointment->id]
                );
            }
        } catch (\Exception $e) {
            Log::warning('FCM notification failed in update: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'user_id' => Auth::id()
            ]);
        }

        return response()->json($appointment);
    }

    // إلغاء موعد
    public function cancel(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('cancel', $appointment);

        $appointment->update([
            'status'      => Appointment::STATUS_CANCELED,
            'canceled_by' => Auth::id(),
        ]);

        // إشعار للطرف الآخر - مع حماية من أخطاء Firebase
        try {
            $other = $appointment->patient_id === Auth::id()
                ? $appointment->doctor
                : $appointment->patient;

            if ($other && $other->fcm_token) {
                $this->fcm->sendToUser(
                    $other->fcm_token,
                    'Appointment canceled',
                    'Appointment canceled number: ' . $appointment->id,
                    ['appointment_id' => $appointment->id]
                );
            }
        } catch (\Exception $e) {
            Log::warning('FCM notification failed in cancel: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'user_id' => Auth::id()
            ]);
        }

        return response()->json($appointment);
    }

    // جلب التخصصات
    public function specializations(): JsonResponse
    {
        $specs = Doctor::distinct()->pluck('specialization');
        return response()->json($specs);
    }

    // جلب الأطباء حسب تخصص
    public function doctorsBySpecialization(string $specialization): JsonResponse
    {
        $doctors = Doctor::where('specialization', $specialization)->get();
        return response()->json($doctors);
    }

    // نطّف الميثود القديم
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Booking by schedule is disabled.'], 403);
    }
}
