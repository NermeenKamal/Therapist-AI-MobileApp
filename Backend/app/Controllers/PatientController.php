<?php
namespace App\Controllers;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class PatientController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        // تسجيل المعلومات للتشخيص
        \Log::info('Update Profile Request', [
            'has_files' => $request->hasFile('profile_image'),
            'all_files' => $request->allFiles(),
            'all_inputs' => $request->all(),
        ]);

        $patient = Auth::user();

        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }

        // تعريف المصفوفات بشكل صريح
        $updatedFields = [];
        $validated = [];

        // معالجة الاسم إذا تم توفيره
        if ($request->has('name') && $request->input('name') !== null) {
            $request->validate(['name' => 'nullable|string|max:255']);
            $validated['name'] = $request->input('name');
            $updatedFields[] = 'Name';
        }

        // معالجة الصورة بطريقة أكثر أمانًا
        try {
            // التحقق من وجود ملف بطرق متعددة
            $hasFile = $request->hasFile('profile_image');
            
            if ($hasFile) {
                \Log::info('Profile image file detected');
                
                // التحقق من صحة الملف
                $request->validate(['profile_image' => 'image|max:5120']);
                
                $profileImage = $request->file('profile_image');
                
                // فحص إضافي للملف
                if ($profileImage && $profileImage->isValid()) {
                    // معلومات الملف للتشخيص
                    $fileInfo = [
                        'mime' => $profileImage->getMimeType(),
                        'size' => $profileImage->getSize(),
                        'name' => $profileImage->getClientOriginalName()
                    ];
                    \Log::info('File information', $fileInfo);
                    
                    // الرفع إلى Cloudinary
                    $uploadResult = Cloudinary::uploadFile($profileImage->getRealPath());
                    
                    if ($uploadResult && method_exists($uploadResult, 'getSecurePath')) {
                        $uploadedFileUrl = $uploadResult->getSecurePath();
                        $validated['profile_image'] = $uploadedFileUrl;
                        $updatedFields[] = 'Profile Image';
                        \Log::info('File uploaded successfully', ['url' => $uploadedFileUrl]);
                    } else {
                        throw new \Exception('Invalid upload result from Cloudinary');
                    }
                } else {
                    \Log::warning('Invalid profile image file');
                    return response()->json([
                        'message' => 'Invalid profile image file',
                        'details' => 'The uploaded file is invalid or corrupted'
                    ], 400);
                }
            } else if ($request->has('profile_image')) {
                // تعامل خاص مع المدخلات غير الصالحة
                $profileImageInput = $request->input('profile_image');
                
                \Log::warning('Invalid profile_image input', [
                    'type' => gettype($profileImageInput),
                    'value' => $profileImageInput
                ]);
                
                return response()->json([
                    'message' => 'Invalid profile image format',
                    'details' => 'profile_image should be a file, not a JSON object or string',
                    'received_type' => gettype($profileImageInput)
                ], 400);
            }
        } catch (\Exception $e) {
            // تسجيل معلومات الخطأ بالتفصيل
            \Log::error('Profile image processing error', [
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
                
                \Log::info('Patient profile updated', [
                    'patient_id' => $patient->id,
                    'updated_fields' => $updatedFields
                ]);
            } catch (\Exception $e) {
                \Log::error('Error updating patient profile', [
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
            'patient' => $patient,
            'updated_fields' => $updatedFields
        ]);
    }

    /**
     * عرض ملف المريض الشخصي
     *
     * @return JsonResponse
     */
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
    
    /**
     * جلب التاريخ الطبي للمريض
     *
     * @return JsonResponse
     */
    public function getMedicalHistory(): JsonResponse
    {
        $patient = Auth::user();
        
        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }
        
        // هنا يمكنك جلب التاريخ الطبي (المواعيد السابقة، التشخيصات، إلخ)
        // على سبيل المثال:
        $medicalHistory = [
            'appointments' => $patient->appointments,
            'prescriptions' => $patient->prescriptions,
            'diagnoses' => $patient->diagnoses,
        ];
        
        return response()->json($medicalHistory);
    }
}
