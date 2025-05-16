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
        // Debug the incoming request data
        \Log::info('Patient registration request data:', $request->all());

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
            // Create the data array first for debugging
            $patientData = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'mobile_number' => $request->input('mobile_number'),
                'national_id' => $request->input('national_id')
            ];

            // Log the data we're about to insert
            \Log::info('Attempting to create patient with data:', $patientData);

            // Try creating with the minimum required fields first
            $patient = new Patient();
            $patient->name = $request->input('name');
            $patient->email = $request->input('email');
            $patient->password = Hash::make($request->input('password'));
            $patient->mobile_number = $request->input('mobile_number');
            $patient->national_id = $request->input('national_id');
            $patient->save();

            $token = $patient->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' => $patient,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Patient registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'request_data' => $request->all(),
                    'validation_passed' => true
                ]
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
