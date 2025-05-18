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
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // أولاً نبحث في جدول الأطباء
        $doctor = Doctor::where('email', $request->email)->first();
        if ($doctor && Hash::check($request->password, $doctor->password)) {
            // نتحقق من الـ OCR verification
            if (!$doctor->is_verified_by_ocr) {
                return response()->json([
                    'message' => 'Your doctor account is pending OCR verification. Please contact support.',
                    'status' => 'pending_verification'
                ], 403);
            }

            $token = $doctor->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Logged in successfully as doctor',
                'user' => $doctor,
                'token' => $token
            ]);
        }

        // ثم نبحث في جدول المرضى
        $patient = Patient::where('email', $request->email)->first();
        if ($patient && Hash::check($request->password, $patient->password)) {
            $token = $patient->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Logged in successfully as patient',
                'user' => $patient,
                'token' => $token
            ]);
        }

        // إذا لم نجد المستخدم في أي من الجدولين
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    public function registerPatient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string',
            'national_id' => 'required|string',
            'date_of_birth' => 'date|nullable',
            'gender' => 'in:male,female|nullable',
            'medical_history' => 'string|nullable',
            'current_medications' => 'string|nullable',
            'allergies' => 'string|nullable',
            'emergency_contact_name' => 'string|nullable',
            'emergency_contact_number' => 'string|nullable',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'fcm_token' => 'string|nullable'
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

            $token = $patient->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Patient registered successfully',
                'user' => $patient,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Patient registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function convertArabicDigitsToEnglish($text)
    {
        $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($arabic, $english, $text);
    }

    public function registerDoctor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string',
            'national_id' => 'required|string',
            'national_id_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'specialization' => 'required|string|in:Behavioral,Mindfulness & Acceptance,Talk Supportive, Relationship & Family, Solution Focused & Goal-Oriented',
            'bio' => 'string|nullable',
            'session_price' => 'numeric|min:0|nullable',
            'medical_license_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'fcm_token' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $isVerifiedByOcr = false;
            $nationalIdPath = null;

            if ($request->hasFile('national_id_file')) {
                $file = $request->file('national_id_file');
                $nationalIdPath = $file->store('national_ids', 'public');

                if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    try {
                        $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR(storage_path('app/public/' . $nationalIdPath));
                        $ocrText = $tesseract->run();

                        $normalizedText = $this->convertArabicDigitsToEnglish($ocrText);
                        $isVerifiedByOcr = str_contains($normalizedText, $request->national_id);
                    } catch (\Exception $e) {
                        Log::error('OCR verification failed:', [
                            'error' => $e->getMessage(),
                            'file' => $nationalIdPath
                        ]);
                    }
                }
            }

            $data = $request->except('national_id_file');
            $data['password'] = Hash::make($request->password);
            $data['national_id_path'] = $nationalIdPath;
            $data['is_verified_by_ocr'] = $isVerifiedByOcr;

            $doctor = Doctor::create($data);

            if ($isVerifiedByOcr) {
                $token = $doctor->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'message' => 'Doctor registered and verified successfully',
                    'user' => $doctor,
                    'token' => $token
                ], 201);
            }

            return response()->json([
                'message' => 'Doctor registration pending OCR verification. Please contact support.',
                'user' => $doctor,
                'status' => 'pending_verification'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Doctor registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
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
