<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    /**
     * Doctor Register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'doctor',
        ]);

        return response()->json([
            'message' => 'Doctor registered successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Doctor Login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role !== 'doctor') {
                return response()->json(['message' => 'Unauthorized: Not a doctor'], 403);
            }

            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'message' => 'Doctor logged in successfully',
                'token'   => $token,
                'user'    => $user,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /**
     * Upload the doctor's documents (ID, specialty, etc.)
     */
    public final function uploadDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'id_card'        => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'specialization' => 'required|string',
            'hospital_image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth()->user();
        if ($user->role !== 'doctor') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $idCardPath   = $request->file('id_card')->store('doctor_documents', 'public');
        $hospitalPath = $request->file('hospital_image')->store('doctor_documents', 'public');

        $user->update([
            'specialization'     => $request->specialization,
            'id_card_path'       => $idCardPath,
            'hospital_card_path' => $hospitalPath,
        ]);

        return response()->json([
            'message' => 'Documents uploaded successfully!',
            'user'    => $user->only('id','name','email','specialization','id_card_path','hospital_card_path')
        ], 201);
    }

    /**
     * Verify the doctor's national ID or documents.
     */
    public final function verifyDoctor(int $doctorId): JsonResponse
    {
        $user = User::where('role','doctor')->find($doctorId);
        if (! $user) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        // هنا يمكنك إضافة منطق الـ OCR للتحقق من ID أو التحقق من الملفات

        $user->update(['is_verified' => true]);

        return response()->json([
            'message' => 'Doctor verified successfully',
            'user'    => $user->only('id','name','email','specialization','is_verified')
        ]);
    }

    /**
     * Extract National ID from image via Python OCR script.
     */
    public function extractNationalIdFromImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        $imagePath = $request->file('image')->getPathname();
        $command   = escapeshellcmd("python3 /path/to/your/python/script.py {$imagePath}");
        $output    = shell_exec($command);
        $nationalId= trim($output);

        return response()->json(['national_id' => $nationalId]);
    }

    /**
     * Update the doctor's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'specialization' => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        if ($user->role !== 'doctor') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->update($request->only('specialization', 'phone', 'address'));

        return response()->json([
            'message' => 'Doctor profile updated successfully',
            'user'    => $user->only('id', 'name', 'email', 'specialization', 'phone', 'address')
        ]);
    }

    /**
     * Get the doctor's appointments.
     */
    public function getAppointments(): JsonResponse
    {
        $user = auth()->user();
        if ($user->role !== 'doctor') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $appointments = $user->appointmentsAsDoctor;

        return response()->json([
            'appointments' => $appointments
        ]);
    }

}
