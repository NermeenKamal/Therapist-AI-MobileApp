<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class PatientController extends Controller
{
    /**
     * Register a new patient.
     *
     * @param\Illuminate\Http\Request  $request
     * @return\Illuminate\Http\JsonResponse
     */


// use App\Models\Patient; // Uncomment if you have a separate Patient model
    public function registerPatient(Request $request)
    {
        \Log::info('Patient registration attempt started', ['data' => $request->except('password')]);

        try {
            // First validate the input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed', ['errors' => $validator->errors()]);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Create user with role = patient
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'patient',
                    'phone_number' => $request->phone_number ?? null,
                    'address' => $request->address ?? null,
                ];

                \Log::info('Attempting to create user with data', array_diff_key($userData, ['password' => '']));

                $user = new User($userData);
                $saved = $user->save();

                \Log::info('User save result', ['saved' => $saved, 'user_id' => $user->id ?? 'null']);

                // If you're using a separate patients table
                if (class_exists('App\\Models\\Patient')) {
                    $patient = new Patient([
                        'user_id' => $user->id,
                        // Add patient-specific fields
                    ]);
                    $patientSaved = $patient->save();
                    \Log::info('Patient save result', ['saved' => $patientSaved, 'patient_id' => $patient->id ?? 'null']);
                }

                // Commit transaction
                DB::commit();

                // Double-check the user was saved by retrieving it again
                $savedUser = User::find($user->id);
                \Log::info('Verification query', ['user_exists' => !is_null($savedUser)]);

                if (!$savedUser) {
                    \Log::error('User not found after save', ['user_id' => $user->id]);
                    return response()->json(['error' => 'Failed to register patient: User record not found after save'], 500);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Patient registered successfully',
                    'user_id' => $user->id
                ]);
            } catch (\Exception $e) {
                // Something went wrong, rollback
                DB::rollBack();
                \Log::error('Exception during registration', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Registration failed: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Patient login function.
     *
     * @param\Illuminate\Http\Request  $request
     * @return\Illuminate\Http\JsonResponse
     */
    public function loginPatient(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'patient')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'user'    => $user,
            'token'   => $token
        ]);
    }

    /**
     * Update the patient's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePatientProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name'    => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        if ($user->role !== 'patient') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->update($request->only('name', 'phone', 'address'));

        return response()->json([
            'message' => 'Patient profile updated successfully',
            'user'    => $user
        ]);
    }

}
