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
        $patient = Auth::user();

        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }

        // Initialize the array properly
        $updatedFields = [];

        // نتحقق يدويًا من الحقول بدلاً من validate مباشرة
        $validated = [];

        if ($request->has('name')) {
            $request->validate(['name' => 'nullable|string|max:255']);
            $validated['name'] = $request->input('name');
            $updatedFields[] = 'Name';
        }

        if ($request->hasFile('profile_image')) {
            $request->validate(['profile_image' => 'nullable|image|max:5120']);
            // Make sure the file is valid before attempting to upload
            if ($request->file('profile_image') && $request->file('profile_image')->isValid()) {
                $uploadedFile = Cloudinary::uploadFile($request->file('profile_image')->getPathname());
                $uploadedFileUrl = $uploadedFile->getSecurePath();
                $validated['profile_image'] = $uploadedFileUrl;
                $updatedFields[] = 'Profile Image';
            }
        }

        if (!empty($validated)) {
            $patient->update($validated);
        }

        $message = count($updatedFields)
            ? 'Updated: ' . implode(' and ', $updatedFields)
            : 'No changes were made';

        return response()->json(['message' => $message, 'patient' => $patient]);
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
