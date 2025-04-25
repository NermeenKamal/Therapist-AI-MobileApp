<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{


    public final function store(Request $request)  : JsonResponse
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

}
