<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function registerPatient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:patients',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string|unique:patients',
            'national_id' => 'required|string|unique:patients',
            'date_of_birth' => 'date|nullable',
            'gender' => 'in:male,female|nullable',
            'medical_history' => 'string|nullable',
            'current_medications' => 'string|nullable',
            'allergies' => 'string|nullable',
            'emergency_contact_name' => 'string|nullable',
            'emergency_contact_number' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $patient = Patient::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mobile_number' => $request->mobile_number,
                'national_id' => $request->national_id,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'medical_history' => $request->medical_history,
                'current_medications' => $request->current_medications,
                'allergies' => $request->allergies,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_number' => $request->emergency_contact_number
            ]);

            $token = $patient->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' => $patient,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerDoctor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:doctors',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string|unique:doctors',
            'national_id' => 'required|string|unique:doctors',
            'specialization' => 'required|string',
            'bio' => 'string|nullable',
            'session_price' => 'required|numeric|min:0',
            'medical_license_path' => 'string|nullable',
            'profile_image' => 'string|nullable',
            'fcm_token' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        
        $doctor = Doctor::create($data);
        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Doctor registered successfully',
            'doctor' => $doctor,
            'token' => $token
        ], 201);
    }

    public function loginPatient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $patient = Patient::where('email', $request->email)->first();

        if (!$patient || !Hash::check($request->password, $patient->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $patient->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'patient' => $patient,
            'token' => $token
        ]);
    }

    public function loginDoctor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doctor = Doctor::where('email', $request->email)->first();

        if (!$doctor || !Hash::check($request->password, $doctor->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'doctor' => $doctor,
            'token' => $token
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
