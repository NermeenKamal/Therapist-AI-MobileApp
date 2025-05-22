<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => 'dqgkjyaqz',
                'api_key' => '867792188442959',
                'api_secret' => 'SlbIpvZ775nMnmMTB0iRffElLsM'
            ]
        ]);
        
        $this->cloudinary = new Cloudinary();
    }

    /**
     * رفع صورة إلى Cloudinary
     */
    public function uploadImage(UploadedFile $file, string $folder = 'uploads'): string
    {
        try {
            $uploadApi = new UploadApi();
            $result = $uploadApi->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'public_id' => uniqid(),
                    'overwrite' => true,
                    'resource_type' => 'auto'
                ]
            );

            Log::info('Image uploaded to Cloudinary successfully', [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url']
            ]);

            return $result['secure_url'];

        } catch (\Exception $e) {
            Log::error('Failed to upload image to Cloudinary', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);

            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * رفع ملف PDF إلى Cloudinary
     */
    public function uploadPdf(UploadedFile $file, string $folder = 'documents'): string
    {
        try {
            $uploadApi = new UploadApi();
            $result = $uploadApi->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'public_id' => uniqid(),
                    'overwrite' => true,
                    'resource_type' => 'raw'
                ]
            );

            Log::info('PDF uploaded to Cloudinary successfully', [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url']
            ]);

            return $result['secure_url'];

        } catch (\Exception $e) {
            Log::error('Failed to upload PDF to Cloudinary', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);

            throw new \Exception('Failed to upload PDF: ' . $e->getMessage());
        }
    }

    /**
     * رفع ملف عام إلى Cloudinary
     */
    public function uploadFile(UploadedFile $file, string $folder = 'uploads'): string
    {
        $mimeType = $file->getMimeType();
        
        if (str_contains($mimeType, 'image/')) {
            return $this->uploadImage($file, $folder);
        } elseif ($mimeType === 'application/pdf') {
            return $this->uploadPdf($file, $folder);
        } else {
            throw new \Exception('Unsupported file type: ' . $mimeType);
        }
    }

    /**
     * حذف ملف من Cloudinary
     */
    public function deleteFile(string $publicId): bool
    {
        try {
            $uploadApi = new UploadApi();
            $result = $uploadApi->destroy($publicId);
            
            Log::info('File deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result']
            ]);

            return $result['result'] === 'ok';

        } catch (\Exception $e) {
            Log::error('Failed to delete file from Cloudinary', [
                'error' => $e->getMessage(),
                'public_id' => $publicId
            ]);

            return false;
        }
    }

    /**
     * استخراج Public ID من الرابط
     */
    public function extractPublicId(string $url): string
    {
        $parts = explode('/', $url);
        $filename = end($parts);
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * توليد رابط محسّن للصورة
     */
    public function getOptimizedImageUrl(string $publicId, int $width = null, int $height = null): string
    {
        try {
            $baseUrl = "https://res.cloudinary.com/dqgkjyaqz/image/upload/";
            
            $transformation = "";
            if ($width && $height) {
                $transformation = "w_{$width},h_{$height},c_fill/";
            } elseif ($width) {
                $transformation = "w_{$width}/";
            } elseif ($height) {
                $transformation = "h_{$height}/";
            }
            
            return $baseUrl . $transformation . $publicId;

        } catch (\Exception $e) {
            Log::error('Failed to generate optimized image URL', [
                'error' => $e->getMessage(),
                'public_id' => $publicId
            ]);

            throw new \Exception('Failed to generate optimized image URL: ' . $e->getMessage());
        }
    }
}
