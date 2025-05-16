<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\OcrService;

class AuthController extends Controller
{
    protected OcrService $ocrService;

    public function __construct(OcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    public function registerPatient(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(compact('user', 'token'), 201);
    }

    public function registerDoctor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|unique:doctors',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string',
            'national_id' => 'required|string|size:14|unique:doctors',
            'id_card_image' => 'required|image',
        ]);

        $data['password'] = Hash::make($data['password']);

        // OCR Verification
        $image = $request->file('id_card_image');
        [$name, $id] = $this->ocrService->extractIdData($image);
        if ($id !== $data['national_id']) {
            return response()->json(['message' => 'OCR verification failed.'], 422);
        }

        $doctor = Doctor::create($data);
        $token = $doctor->createToken('api-token')->plainTextToken;

        return response()->json(compact('doctor', 'token'), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(compact('user', 'token'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
