<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        // Log the incoming request
        Log::info('Login attempt', [
            'email' => $request->email,
            'user_type' => $request->user_type,
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'user_type' => 'required|in:patient,doctor'
        ]);

        if ($validator->fails()) {
            Log::error('Login validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->user_type === 'patient') {
                Log::info('Attempting patient login');
                return $this->loginPatient($request);
            } else {
                Log::info('Attempting doctor login');
                return $this->loginDoctor($request);
            }
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerPatient(Request $request): JsonResponse
    {
        // التحقق من عدم وجود نفس البريد في جدول الأطباء
        if (Doctor::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'This email is already registered as a doctor',
                'errors' => ['email' => ['Email is already registered as a doctor']]
            ], 422);
        }

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
            $patient = new Patient();
            $patient->name = $request->input('name');
            $patient->email = $request->input('email');
            $patient->password = Hash::make($request->input('password'));
            $patient->mobile_number = $request->input('mobile_number');
            $patient->national_id = $request->input('national_id');
            $patient->save();

            // Create token with try-catch
            try {
                $token = $patient->createToken('auth_token')->plainTextToken;
            } catch (\Exception $e) {
                \Log::error('Token creation failed:', [
                    'error' => $e->getMessage(),
                    'patient_id' => $patient->id
                ]);
                $token = null;
            }

            return response()->json([
                'message' => 'Patient registered successfully',
                'patient' => $patient,
                'token' => $token ?? 'Token creation failed, please login to get a new token'
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
        // التحقق من عدم وجود نفس البريد في جدول المرضى
        if (Patient::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'This email is already registered as a patient',
                'errors' => ['email' => ['Email is already registered as a patient']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:doctors',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string|unique:doctors',
            'national_id' => 'required|string|unique:doctors',
            'national_id_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'specialization' => 'required|string',
            'bio' => 'string|nullable',
            'session_price' => 'numeric|min:0|nullable',
            'medical_license_path' => 'string|nullable',
            'profile_image' => 'string|nullable',
            'fcm_token' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->hasFile('national_id_file')) {
                $file = $request->file('national_id_file');
                $nationalIdPath = $file->store('national_ids', 'public');
                
                $isVerifiedByOcr = false;
                
                if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    try {
                        $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR(storage_path('app/public/' . $nationalIdPath));
                        $ocrText = $tesseract->run();
                        $isVerifiedByOcr = str_contains($ocrText, $request->national_id);
                    } catch (\Exception $e) {
                        \Log::error('OCR verification failed:', [
                            'error' => $e->getMessage(),
                            'file' => $nationalIdPath
                        ]);
                    }
                }
            }

            $data = $request->except('national_id_file');
            $data['password'] = Hash::make($request->password);
            $data['national_id_path'] = $nationalIdPath ?? null;
            $data['is_verified_by_ocr'] = $isVerifiedByOcr ?? false;
            
            $doctor = Doctor::create($data);
            $token = $doctor->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Doctor registered successfully' . ($isVerifiedByOcr ? ' and verified by OCR' : ' but pending OCR verification'),
                'doctor' => $doctor,
                'token' => $token,
                'ocr_verified' => $isVerifiedByOcr ?? false
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Doctor registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function loginPatient(Request $request): JsonResponse
    {
        // التحقق من وجود نفس البريد في جدول الأطباء
        $doctorExists = Doctor::where('email', $request->email)->exists();
        if ($doctorExists) {
            return response()->json([
                'message' => 'This email is registered as a doctor. Please use doctor login.',
                'correct_type' => 'doctor'
            ], 400);
        }

        $patient = Patient::where('email', $request->email)->first();

        if (!$patient || !Hash::check($request->password, $patient->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // إنشاء token مع تحديد نوع المستخدم في الـ abilities
        $token = $patient->createToken('auth_token', ['role:patient'])->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $patient,
            'token' => $token,
            'user_type' => 'patient'
        ]);
    }

    protected function loginDoctor(Request $request): JsonResponse
    {
        // التحقق من وجود نفس البريد في جدول المرضى
        $patientExists = Patient::where('email', $request->email)->exists();
        if ($patientExists) {
            return response()->json([
                'message' => 'This email is registered as a patient. Please use patient login.',
                'correct_type' => 'patient'
            ], 400);
        }

        $doctor = Doctor::where('email', $request->email)->first();

        if (!$doctor || !Hash::check($request->password, $doctor->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$doctor->is_verified_by_ocr) {
            return response()->json([
                'message' => 'Your account is pending OCR verification. Please contact support.',
                'status' => 'pending_verification'
            ], 403);
        }

        // إنشاء token مع تحديد نوع المستخدم في الـ abilities
        $token = $doctor->createToken('auth_token', ['role:doctor'])->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => $doctor,
            'token' => $token,
            'user_type' => 'doctor'
        ]);
    }

    /**
     * Get the authenticated user with their type
     */
    public function getAuthenticatedUser(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = 'unknown';

        // تحديد نوع المستخدم من الـ token abilities
        if ($user->currentAccessToken()->can('role:patient')) {
            $userType = 'patient';
            // تحميل بيانات المريض الكاملة
            $user = Patient::find($user->id);
        } elseif ($user->currentAccessToken()->can('role:doctor')) {
            $userType = 'doctor';
            // تحميل بيانات الطبيب الكاملة
            $user = Doctor::find($user->id);
        }

        return response()->json([
            'user' => $user,
            'user_type' => $userType
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Logout failed', 'error' => $e->getMessage()], 500);
        }
    }
} 
