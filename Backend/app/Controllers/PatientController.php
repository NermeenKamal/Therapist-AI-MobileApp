<?php
namespace App\Controllers;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Cloudinary\Cloudinary;

class PatientController extends Controller
{
    private function getCloudinary(): Cloudinary
    {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        Log::info('Update Profile Request', [
            'has_files' => $request->hasFile('profile_image'),
            'all_files' => $request->allFiles(),
            'all_inputs' => $request->all(),
        ]);

        $patient = Auth::user();

        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }

        $updatedFields = [];
        $validated = [];

        if ($request->has('name') && $request->input('name') !== null) {
            $request->validate(['name' => 'nullable|string|max:255']);
            $validated['name'] = $request->input('name');
            $updatedFields[] = 'Name';
        }

        try {
            $hasFile = $request->hasFile('profile_image');
            
            if ($hasFile) {
                Log::info('Profile image file detected');

                $request->validate(['profile_image' => 'image|max:5120']);

                $profileImage = $request->file('profile_image');

                if ($profileImage && $profileImage->isValid()) {
                    $fileInfo = [
                        'mime' => $profileImage->getMimeType(),
                        'size' => $profileImage->getSize(),
                        'name' => $profileImage->getClientOriginalName()
                    ];
                    Log::info('File information', $fileInfo);

                    if (!$this->isCloudinaryConfigured()) {
                        Log::error('Cloudinary configuration missing or invalid');
                        return response()->json([
                            'message' => 'Server configuration error',
                            'details' => 'Image upload service is not properly configured'
                        ], 500);
                    }

                    try {
                        $cloudinary = $this->getCloudinary();

                        $uploadResult = $cloudinary->uploadApi()->upload(
                            $profileImage->getRealPath(),
                            [
                                'folder' => 'profile_images',
                                'resource_type' => 'image'
                            ]
                        );

                        if ($uploadResult && isset($uploadResult['secure_url'])) {
                            $uploadedFileUrl = $uploadResult['secure_url'];
                            $validated['profile_image'] = $uploadedFileUrl;
                            $updatedFields[] = 'Profile Image';
                            Log::info('File uploaded successfully', ['url' => $uploadedFileUrl]);
                        } else {
                            Log::error('Invalid upload result', [
                                'result_type' => gettype($uploadResult),
                                'result' => $uploadResult
                            ]);
                            throw new Exception('Invalid upload result from Cloudinary');
                        }
                    } catch (Exception $cloudinaryError) {
                        Log::error('Cloudinary upload error', [
                            'message' => $cloudinaryError->getMessage(),
                            'file' => $cloudinaryError->getFile(),
                            'line' => $cloudinaryError->getLine(),
                            'trace' => $cloudinaryError->getTraceAsString()
                        ]);

                        try {
                            $localPath = $profileImage->store('profile_images', 'public');
                            $validated['profile_image'] = asset('storage/' . $localPath);
                            $updatedFields[] = 'Profile Image (Local Storage)';
                            Log::info('File stored locally as fallback', ['path' => $localPath]);

                            return response()->json([
                                'message' => 'Profile image updated successfully (stored locally)',
                                'profile_image' => $validated['profile_image']
                            ]);
                        } catch (Exception $localStorageError) {
                            Log::error('Local storage error', [
                                'message' => $localStorageError->getMessage()
                            ]);

                            return response()->json([
                                'message' => 'Error uploading image',
                                'error' => 'Failed to store image in both cloud and local storage'
                            ], 500);
                        }
                    }
                } else {
                    Log::warning('Invalid profile image file');
                    return response()->json([
                        'message' => 'Invalid profile image file',
                        'details' => 'The uploaded file is invalid or corrupted'
                    ], 400);
                }
            } else if ($request->has('profile_image')) {
                $profileImageInput = $request->input('profile_image');

                Log::warning('Invalid profile_image input', [
                    'type' => gettype($profileImageInput),
                    'value' => $profileImageInput
                ]);

                return response()->json([
                    'message' => 'Invalid profile image format',
                    'details' => 'profile_image should be a file, not a JSON object or string',
                    'received_type' => gettype($profileImageInput)
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Profile image processing error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error processing profile image',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }

        if (!empty($validated)) {
            try {
                $patient->update($validated);

                Log::info('Patient profile updated', [
                    'patient_id' => $patient->id,
                    'updated_fields' => $updatedFields
                ]);
            } catch (Exception $e) {
                Log::error('Error updating patient profile', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                return response()->json([
                    'message' => 'Error updating profile',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $message = !empty($updatedFields)
            ? 'Updated: ' . implode(' and ', $updatedFields)
            : 'No changes were made';

        if (count($updatedFields) === 1 && in_array('Profile Image', $updatedFields)) {
            return response()->json([
                'message' => 'Profile image updated successfully',
                'profile_image' => $validated['profile_image']
            ]);
        }

        return response()->json([
            'message' => $message,
            'patient' => $patient,
            'updated_fields' => $updatedFields
        ]);
    }

    private function isCloudinaryConfigured(): bool
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        Log::info('Cloudinary configuration check', [
            'cloud_name_set' => !empty($cloudName),
            'api_key_set' => !empty($apiKey),
            'api_secret_set' => !empty($apiSecret)
        ]);

        return !empty($cloudName) && !empty($apiKey) && !empty($apiSecret);
    }

    public function showProfile(): JsonResponse
    {
        $patient = Auth::user();

        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }

        return response()->json([
            'id' => $patient->id,
            'name' => $patient->name,
            'profile_image' => $patient->profile_image,
        ]);
    }

    public function getMedicalHistory(): JsonResponse
    {
        $patient = Auth::user();

        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }

        $medicalHistory = [
            'appointments' => $patient->appointments,
            'prescriptions' => $patient->prescriptions,
            'diagnoses' => $patient->diagnoses,
        ];

        return response()->json($medicalHistory);
    }

    public function testCloudinaryConfig(): JsonResponse
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        return response()->json([
            'cloud_name_set' => !empty($cloudName),
            'api_key_set' => !empty($apiKey),
            'api_secret_set' => !empty($apiSecret),
            'all_set' => !empty($cloudName) && !empty($apiKey) && !empty($apiSecret)
        ]);
    }
}
