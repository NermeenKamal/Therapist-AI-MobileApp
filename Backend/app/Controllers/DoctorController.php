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
     * تهيئة كائن Cloudinary
     *
     * @return Cloudinary
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
     * التحقق من تكوين Cloudinary
     *
     * @return bool
     */
    private function isCloudinaryConfigured(): bool
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        
        // تسجيل معلومات التكوين للتشخيص (مع إخفاء المعلومات الحساسة)
        Log::info('Cloudinary configuration check', [
            'cloud_name_set' => !empty($cloudName),
            'api_key_set' => !empty($apiKey),
            'api_secret_set' => !empty($apiSecret)
        ]);
        
        return !empty($cloudName) && !empty($apiKey) && !empty($apiSecret);
    }

    // تعديل بيانات البروفايل للدكتور الحالي
    public function updateProfile(Request $request): JsonResponse
    {
        // تسجيل المعلومات للتشخيص
        Log::info('Doctor Update Profile Request', [
            'has_files' => $request->hasFile('profile_image'),
            'all_files' => $request->allFiles(),
            'all_inputs' => $request->all(),
        ]);

        $doctor = Auth::user();

        if (!$doctor || !$doctor instanceof Doctor) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // تعريف المصفوفات بشكل صريح
        $updatedFields = [];
        $validated = [];

        // التحقق من البيانات الأساسية
        $request->validate([
            'bio' => 'nullable|string',
            'session_price' => 'nullable|numeric|min:0',
            'profile_image' => 'nullable|image|max:4096', // 4MB
            'clinic_address' => 'nullable|string|max:255', // إضافة التحقق من عنوان العيادة
        ]);

        // إضافة البيانات المتحقق منها
        if ($request->has('bio')) {
            $validated['bio'] = $request->input('bio');
            $updatedFields[] = 'Bio';
        }

        if ($request->has('session_price')) {
            $validated['session_price'] = $request->input('session_price');
            $updatedFields[] = 'Session Price';
        }

        // إضافة عنوان العيادة إذا تم توفيره
        if ($request->has('clinic_address')) {
            $validated['clinic_address'] = $request->input('clinic_address');
            $updatedFields[] = 'Clinic Address';
        }

        // معالجة الصورة بطريقة أكثر أمانًا
        try {
            // التحقق من وجود ملف
            $hasFile = $request->hasFile('profile_image');
            
            if ($hasFile) {
                Log::info('Doctor profile image file detected');
                
                $profileImage = $request->file('profile_image');
                
                // فحص إضافي للملف
                if ($profileImage && $profileImage->isValid()) {
                    // معلومات الملف للتشخيص
                    $fileInfo = [
                        'mime' => $profileImage->getMimeType(),
                        'size' => $profileImage->getSize(),
                        'name' => $profileImage->getClientOriginalName()
                    ];
                    Log::info('File information', $fileInfo);
                    
                    // التحقق من تكوين Cloudinary قبل المتابعة
                    if (!$this->isCloudinaryConfigured()) {
                        Log::error('Cloudinary configuration missing or invalid');
                        return response()->json([
                            'message' => 'Server configuration error',
                            'details' => 'Image upload service is not properly configured'
                        ], 500);
                    }
                    
                    try {
                        // استخدام SDK الرسمي من Cloudinary
                        $cloudinary = $this->getCloudinary();
                        
                        // رفع الملف إلى Cloudinary
                        $uploadResult = $cloudinary->uploadApi()->upload(
                            $profileImage->getRealPath(),
                            [
                                'folder' => 'doctor_images',
                                'resource_type' => 'image'
                            ]
                        );
                        
                        // التحقق من نتيجة الرفع
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
                        
                        // حل بديل: احتفظ بالصورة محليًا إذا فشل Cloudinary
                        try {
                            $localPath = $profileImage->store('doctor_images', 'public');
                            $validated['profile_image'] = asset('storage/' . $localPath);
                            $updatedFields[] = 'Profile Image (Local Storage)';
                            Log::info('File stored locally as fallback', ['path' => $localPath]);
                            
                            // إعلام المستخدم بأن الصورة تم تخزينها محليًا
                            return response()->json([
                                'message' => 'Image stored locally due to cloud storage issue',
                                'doctor' => $doctor->fresh(),
                                'updated_fields' => $updatedFields
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
                    Log::warning('Invalid doctor profile image file');
                    return response()->json([
                        'message' => 'Invalid profile image file',
                        'details' => 'The uploaded file is invalid or corrupted'
                    ], 400);
                }
            } else if ($request->has('profile_image')) {
                // تعامل خاص مع المدخلات غير الصالحة
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
            // تسجيل معلومات الخطأ بالتفصيل
            Log::error('Doctor profile image processing error', [
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

        // تحديث بيانات الدكتور
        if (!empty($validated)) {
            try {
                $doctor->update($validated);
                
                Log::info('Doctor profile updated', [
                    'doctor_id' => $doctor->id,
                    'updated_fields' => $updatedFields
                ]);
            } catch (Exception $e) {
                Log::error('Error updating doctor profile', [
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

        return response()->json([
            'message' => $message, 
            'doctor' => $doctor,
            'updated_fields' => $updatedFields
        ]);
    }

    // جلب كل الدكاترة حسب التخصص مع متوسط التقييم
    public function index(Request $request): JsonResponse
    {
        $specialty = $request->query('specialty');
        $query = Doctor::query();
        if ($specialty) {
            $query->where('specialization', $specialty);
        }
        $doctors = $query->get();
        $doctors = $doctors->map(function($doctor) {
            $avg = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'profile_image' => $doctor->profile_image,
                'specialization' => $doctor->specialization,
                'session_price' => $doctor->session_price,
                'average_rating' => $avg,
                'bio' => $doctor->bio,
                'clinic_address' => $doctor->clinic_address, // إضافة عنوان العيادة للاستجابة
            ];
        });
        return response()->json($doctors);
    }

    // جلب تفاصيل دكتور واحد

// في DoctorController.php - استبدل الـ show method بهذا الكود المُحسن:

public function show($id): JsonResponse
{
    try {
        // إضافة تسجيل للتشخيص
        Log::info('Fetching doctor details', ['doctor_id' => $id]);
        
        // التحقق من وجود الدكتور أولاً
        $doctor = Doctor::find($id);
        
        if (!$doctor) {
            Log::warning('Doctor not found', ['doctor_id' => $id]);
            return response()->json([
                'message' => 'Doctor not found'
            ], 404);
        }
        
        // التحقق من حالة التحقق للدكتور
        if (!$doctor->email_verified || !$doctor->is_verified_by_ocr) {
            Log::info('Doctor not fully verified', [
                'doctor_id' => $id,
                'email_verified' => $doctor->email_verified,
                'ocr_verified' => $doctor->is_verified_by_ocr
            ]);
        }
        
        // جلب الجداول الزمنية
        $schedules = DoctorSchedule::where('doctor_id', $doctor->id)->get();
        Log::info('Doctor schedules found', ['count' => $schedules->count()]);
        
        // حساب متوسط التقييم
        $avgRating = ChatRating::where('doctor_id', $doctor->id)->avg('sentiment_score');
        $ratingsCount = ChatRating::where('doctor_id', $doctor->id)->count();
        
        Log::info('Doctor ratings', [
            'average' => $avgRating,
            'count' => $ratingsCount
        ]);
        
        // تجهيز البيانات للإرجاع
        $doctorData = [
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $doctor->email, // إضافة الإيميل للتشخيص
            'profile_image' => $doctor->profile_image,
            'specialization' => $doctor->specialization,
            'session_price' => $doctor->session_price,
            'bio' => $doctor->bio,
            'clinic_address' => $doctor->clinic_address,
            'schedules' => $schedules,
            'average_rating' => $avgRating ? round($avgRating, 2) : null,
            'ratings_count' => $ratingsCount,
            'email_verified' => $doctor->email_verified,
            'is_verified_by_ocr' => $doctor->is_verified_by_ocr,
            'created_at' => $doctor->created_at,
            'updated_at' => $doctor->updated_at
        ];
        
        Log::info('Doctor data prepared successfully', ['doctor_id' => $id]);
        
        return response()->json($doctorData);
        
    } catch (\Exception $e) {
        Log::error('Error fetching doctor details', [
            'doctor_id' => $id,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'message' => 'Error fetching doctor details',
            'error' => $e->getMessage()
        ], 500);
    }
}

// إضافة هذا الـ route في ملف routes/api.php داخل الـ middleware المحمي:
// Route::get('/doctors/{id}/debug', [DoctorController::class, 'debug']);

// إضافة method للتشخيص (اختياري)
public function debug($id): JsonResponse
{
    try {
        // فحص وجود الدكتور في قاعدة البيانات
        $doctorExists = Doctor::where('id', $id)->exists();
        $doctorCount = Doctor::count();
        $allDoctorIds = Doctor::pluck('id')->toArray();
        
        // فحص الجداول الزمنية
        $schedulesCount = DoctorSchedule::where('doctor_id', $id)->count();
        
        // فحص التقييمات
        $ratingsCount = ChatRating::where('doctor_id', $id)->count();
        
        return response()->json([
            'doctor_exists' => $doctorExists,
            'total_doctors_count' => $doctorCount,
            'all_doctor_ids' => $allDoctorIds,
            'schedules_count' => $schedulesCount,
            'ratings_count' => $ratingsCount,
            'requested_id' => $id,
            'id_type' => gettype($id)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'requested_id' => $id
        ], 500);
    }
}

    /**
     * اختبار تكوين Cloudinary
     *
     * @return JsonResponse
     */
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
