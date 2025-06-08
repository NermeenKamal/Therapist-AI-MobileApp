<?php

namespace App\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\DoctorSchedule;
use App\Models\ChatRating;
use Illuminate\Support\Facades\Log;
use Exception;
use Cloudinary\Cloudinary;

class DoctorController extends Controller
{
    /**
     * Initialize Cloudinary instance
     */
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

    /**
     * Check if Cloudinary is properly configured
     */
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

    /**
     * Update authenticated doctor's profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        Log::info('Doctor Update Profile Request', [
            'has_files' => $request->hasFile('profile_image'),
            'all_files' => $request->allFiles(),
            'all_inputs' => $request->all(),
        ]);

        $doctor = Auth::user();

        if (!$doctor || !$doctor instanceof Doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }

        $updatedFields = [];
        $validated = [];

        // Validate input data
        try {
            $request->validate([
                'bio' => 'nullable|string|max:1000',
                'session_price' => 'nullable|numeric|min:0',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
                'clinic_address' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Process basic fields
        if ($request->has('bio')) {
            $validated['bio'] = $request->input('bio');
            $updatedFields[] = 'Bio';
        }

        if ($request->has('session_price')) {
            $validated['session_price'] = $request->input('session_price');
            $updatedFields[] = 'Session Price';
        }

        if ($request->has('clinic_address')) {
            $validated['clinic_address'] = $request->input('clinic_address');
            $updatedFields[] = 'Clinic Address';
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $profileImage = $request->file('profile_image');
            
            if ($profileImage && $profileImage->isValid()) {
                Log::info('Processing profile image', [
                    'mime' => $profileImage->getMimeType(),
                    'size' => $profileImage->getSize(),
                    'name' => $profileImage->getClientOriginalName()
                ]);
                
                if (!$this->isCloudinaryConfigured()) {
                    Log::error('Cloudinary not configured properly');
                    return response()->json([
                        'success' => false,
                        'message' => 'Image upload service is not configured'
                    ], 500);
                }
                
                try {
                    $cloudinary = $this->getCloudinary();
                    $uploadResult = $cloudinary->uploadApi()->upload(
                        $profileImage->getRealPath(),
                        [
                            'folder' => 'doctor_images',
                            'resource_type' => 'image',
                            'transformation' => [
                                'width' => 500,
                                'height' => 500,
                                'crop' => 'fill'
                            ]
                        ]
                    );
                    
                    if ($uploadResult && isset($uploadResult['secure_url'])) {
                        $validated['profile_image'] = $uploadResult['secure_url'];
                        $updatedFields[] = 'Profile Image';
                        Log::info('Image uploaded successfully', ['url' => $uploadResult['secure_url']]);
                    }
                } catch (Exception $e) {
                    Log::error('Cloudinary upload failed', ['error' => $e->getMessage()]);
                    
                    // Fallback to local storage
                    try {
                        $localPath = $profileImage->store('doctor_images', 'public');
                        $validated['profile_image'] = asset('storage/' . $localPath);
                        $updatedFields[] = 'Profile Image (Local)';
                    } catch (Exception $localError) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to upload image'
                        ], 500);
                    }
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file'
                ], 400);
            }
        }

        // Update doctor profile
        if (!empty($validated)) {
            try {
                $doctor->update($validated);
                Log::info('Doctor profile updated', [
                    'doctor_id' => $doctor->id,
                    'updated_fields' => $updatedFields
                ]);
            } catch (Exception $e) {
                Log::error('Failed to update doctor profile', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => !empty($updatedFields) 
                ? 'Profile updated: ' . implode(', ', $updatedFields)
                : 'No changes were made',
            'data' => [
                'doctor' => $doctor->fresh(),
                'updated_fields' => $updatedFields
            ]
        ]);
    }

    /**
     * Get all doctors with optional specialty filter
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $specialty = $request->query('specialty');
            $perPage = $request->query('per_page', 15);
            $verified_only = $request->query('verified_only', false);
            
            Log::info('Fetching doctors list', [
                'specialty_filter' => $specialty,
                'verified_only' => $verified_only
            ]);
            
            $query = Doctor::query();
            
            // Apply specialty filter
            if ($specialty) {
                $query->where('specialization', $specialty);
            }
            
            // Apply verification filter
            if ($verified_only) {
                $query->where('email_verified', true)
                      ->where('is_verified_by_ocr', true);
            }
            
            // Get doctors with pagination
            $doctors = $query->paginate($perPage);
            
            // Transform data
            $doctorsData = $doctors->map(function ($doctor) {
                $avgRating = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
                $ratingsCount = ChatRating::where('doctor_id', $doctor->id)->count();
                
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'profile_image' => $doctor->profile_image,
                    'specialization' => $doctor->specialization,
                    'session_price' => $doctor->session_price,
                    'bio' => $doctor->bio,
                    'clinic_address' => $doctor->clinic_address,
                    'average_rating' => $avgRating ? round($avgRating, 2) : null,
                    'ratings_count' => $ratingsCount,
                    'email_verified' => $doctor->email_verified,
                    'is_verified_by_ocr' => $doctor->is_verified_by_ocr
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $doctorsData,
                'pagination' => [
                    'current_page' => $doctors->currentPage(),
                    'per_page' => $doctors->perPage(),
                    'total' => $doctors->total(),
                    'last_page' => $doctors->lastPage(),
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Error fetching doctors', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctors'
            ], 500);
        }
    }

    /**
     * Get single doctor details
     */
    public function show($id): JsonResponse
    {
        try {
            Log::info('Fetching doctor details', ['doctor_id' => $id]);
            
            $doctor = Doctor::select([
                'id', 'name', 'email', 'mobile_number', 'specialization',
                'bio', 'clinic_address', 'session_price', 'profile_image',
                'medical_license_path', 'license_number', 'email_verified',
                'is_verified_by_ocr', 'created_at'
            ])->find($id);

            if (!$doctor) {
                Log::warning('Doctor not found', ['doctor_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            // Get schedules
            $schedules = DoctorSchedule::where('doctor_id', $doctor->id)
                ->select(['id', 'day', 'start_time', 'end_time'])
                ->get();

            // Calculate ratings
            $avgRating = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
            $ratingsCount = ChatRating::where('doctor_id', $doctor->id)->count();

            $responseData = [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'mobile_number' => $doctor->mobile_number,
                'profile_image' => $doctor->profile_image,
                'specialization' => $doctor->specialization,
                'session_price' => $doctor->session_price,
                'bio' => $doctor->bio,
                'clinic_address' => $doctor->clinic_address,
                'average_rating' => $avgRating ? round($avgRating, 2) : null,
                'ratings_count' => $ratingsCount,
                'email_verified' => $doctor->email_verified,
                'is_verified_by_ocr' => $doctor->is_verified_by_ocr,
                'medical_license' => $doctor->medical_license_path,
                'license_number' => $doctor->license_number,
                'schedules' => $schedules,
                'joined_at' => $doctor->created_at->format('Y-m-d')
            ];

            Log::info('Successfully fetched doctor details', ['doctor_id' => $id]);
            
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch doctor details', [
                'doctor_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch doctor details'
            ], 500);
        }
    }

    /**
     * Test Cloudinary configuration
     */
    public function testCloudinaryConfig(): JsonResponse
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        
        return response()->json([
            'success' => true,
            'data' => [
                'cloud_name_set' => !empty($cloudName),
                'api_key_set' => !empty($apiKey),
                'api_secret_set' => !empty($apiSecret),
                'all_configured' => !empty($cloudName) && !empty($apiKey) && !empty($apiSecret)
            ]
        ]);
    }
}
