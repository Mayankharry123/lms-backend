<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;

trait HandlesFileUploads
{
    /**
     * Default file size limits (in KB)
     */
    protected array $defaultFileSizeLimits = [
        'image' => 5120,      // 5MB
        'pdf' => 51200,       // 50MB
        'video' => 51200,     // 50MB
        'document' => 51200,  // 50MB
    ];

    /**
     * Allowed file extensions by type
     */
    protected array $allowedExtensions = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
        'pdf' => ['pdf'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'],
        'document' => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf'],
        'audio' => ['mp3', 'wav', 'ogg', 'aac', 'm4a', 'wma'],
    ];

    /**
     * MIME types by file type
     */
    protected array $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/x-icon',
        ],
        'pdf' => [
            'application/pdf',
        ],
        'video' => [
            'video/mp4',
            'video/avi',
            'video/quicktime',
            'video/x-ms-wmv',
            'video/x-flv',
            'video/webm',
            'video/x-matroska',
            'video/3gpp',
        ],
        'document' => [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/rtf',
        ],
        'audio' => [
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/aac',
            'audio/mp4',
            'audio/x-ms-wma',
        ],
    ];

    /**
     * Detect file type based on its extension
     *
     * @param UploadedFile $file
     * @return string File type (image, pdf, video, document, audio)
     */
    protected function detectFileType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        foreach ($this->allowedExtensions as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        
        return 'document'; // Default fallback
    }

    /**
     * Upload a single file
     *
     * @param UploadedFile $file
     * @param string $type Type of file (image, pdf, video, document, audio)
     * @param string $folder Folder path in storage (e.g., 'uploads/images')
     * @param array $options Additional options (sizeLimit, allowedExtensions, etc.)
     * @return array Returns array with 'path', 'url', 'name', 'size', 'mime_type'
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadFile(
        UploadedFile $file,
        string $type = 'image',
        string $folder = 'uploads',
        array $options = []
    ): array {
        // Validate file type
        $this->validateFileType($file, $type, $options);

        // Validate file size
        $sizeLimit = $options['sizeLimit'] ?? $this->defaultFileSizeLimits[$type] ?? 5120;
        $this->validateFileSize($file, $sizeLimit);

        // Generate unique filename
        $filename = $this->generateFilename($file, $options);

        // Get storage disk
        $disk = $options['disk'] ?? config('filesystems.default', 'local');

        // Store file
        try {
            $path = $file->storeAs($folder, $filename, $disk);

            // Get file URL
            $url = $this->generateFileUrl($path, $disk);

            return [
                'path' => $path,
                'url' => $url,
                'name' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param array $files Array of UploadedFile objects
     * @param string $type Type of file
     * @param string $folder Folder path in storage
     * @param array $options Additional options
     * @return array Returns array of uploaded file information
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadFiles(
        array $files,
        string $type = 'image',
        string $folder = 'uploads',
        array $options = []
    ): array {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $type, $folder, $options);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Validate file type
     *
     * @param UploadedFile $file
     * @param string $type
     * @param array $options
     * @return void
     * @throws ValidationException
     */
    protected function validateFileType(UploadedFile $file, string $type, array $options = []): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $clientMimeType = $file->getClientMimeType();

        // Get allowed extensions for this type
        $allowedExtensions = $options['allowedExtensions'] 
            ?? $this->allowedExtensions[$type] 
            ?? [];

        // Get allowed MIME types for this type
        $allowedMimeTypes = $options['allowedMimeTypes'] 
            ?? $this->allowedMimeTypes[$type] 
            ?? [];

        // Check extension first
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => "File extension '{$extension}' is not allowed. Allowed extensions: " . implode(', ', $allowedExtensions)
            ]);
        }

        if (empty($allowedMimeTypes)) {
            return;
        }

        $mimeTypeAllowed = in_array($mimeType, $allowedMimeTypes)
            || in_array($clientMimeType, $allowedMimeTypes);

        $fallbackMimeTypes = [
            'application/octet-stream',
            'text/html',
            'application/force-download',
            'application/x-msdownload'
        ];

        if (!$mimeTypeAllowed
            && !in_array($mimeType, $fallbackMimeTypes)
            && !in_array($clientMimeType, $fallbackMimeTypes)
        ) {
            throw ValidationException::withMessages([
                'file' => "File type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedMimeTypes)
            ]);
        }
    }

    /**
     * Validate file size
     *
     * @param UploadedFile $file
     * @param int $sizeLimit Size limit in KB
     * @return void
     * @throws ValidationException
     */
    protected function validateFileSize(UploadedFile $file, int $sizeLimit): void
    {
        $fileSizeKB = $file->getSize() / 1024; // Convert bytes to KB
        $sizeLimitMB = $sizeLimit / 1024; // Convert KB to MB

        if ($fileSizeKB > $sizeLimit) {
            throw ValidationException::withMessages([
                'file' => "File size ({$this->formatFileSize($file->getSize())}) exceeds the maximum allowed size of {$this->formatFileSize($sizeLimit * 1024)}"
            ]);
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @param array $options
     * @return string
     */
    protected function generateFilename(UploadedFile $file, array $options = []): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Use custom filename if provided
        if (isset($options['filename'])) {
            return $options['filename'] . '.' . $extension;
        }

        // Generate unique filename with prefix, keeping the original name format
        $filename = $options['prefix'] ?? '';
        $filename .= $originalName . '_' . time() . '_' . Str::random(8);
        $filename .= '.' . $extension;

        return $filename;
    }

    /**
     * Delete file from storage
     *
     * @param string $path File path
     * @param string|null $disk Storage disk
     * @return bool
     */
    public function deleteFile(string $path, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? config('filesystems.default', 'local');
            return Storage::disk($disk)->delete($path);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if file exists
     *
     * @param string $path File path
     * @param string|null $disk Storage disk
     * @return bool
     */
    public function fileExists(string $path, ?string $disk = null): bool
    {
        try {
            $disk = $disk ?? config('filesystems.default', 'local');
            return Storage::disk($disk)->exists($path);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get file URL
     *
     * @param string $path File path
     * @param string|null $disk Storage disk
     * @return string|null
     */
    public function getFileUrl(string $path, ?string $disk = null): ?string
    {
        try {
            $disk = $disk ?? config('filesystems.default', 'local');
            return $this->generateFileUrl($path, $disk);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate file URL based on storage disk type
     *
     * @param string $path File path
     * @param string $disk Storage disk name
     * @return string
     */
    protected function generateFileUrl(string $path, string $disk): string
    {
        $diskConfig = config("filesystems.disks.{$disk}", []);
        $driver = $diskConfig['driver'] ?? 'local';

        // First try the disk-specific URL generator if available.
        try {
            /** @var FilesystemAdapter $storage */
            $storage = Storage::disk($disk);
            $diskUrl = $storage->url($path);

            if (!empty($diskUrl)) {

                // Agar relative URL hai to domain add karo
                if (!Str::startsWith($diskUrl, ['http://', 'https://'])) {
                    $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
                    return $baseUrl . '/' . ltrim($diskUrl, '/');
                }

                return $diskUrl;
            }
        } catch (Exception $e) {
        }

        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

        if ($driver === 's3') {
            if (isset($diskConfig['url'])) {
                return rtrim($diskConfig['url'], '/') . '/' . ltrim($path, '/');
            }
            if (isset($diskConfig['bucket'])) {
                $region = $diskConfig['region'] ?? 'us-east-1';
                $bucket = $diskConfig['bucket'];
                return "https://{$bucket}.s3.{$region}.amazonaws.com/" . ltrim($path, '/');
            }
        }

        if ($disk === 'public') {
            return $baseUrl . '/storage/' . ltrim($path, '/');
        }

        if ($driver === 'local') {
            $visibility = $diskConfig['visibility'] ?? 'private';
            $root = $diskConfig['root'] ?? storage_path('app');

            if ($visibility === 'public') {
                $publicPath = function_exists('public_path')
                    ? public_path()
                    : app()->basePath('public');
                if (strpos($root, $publicPath) === 0) {
                    $relativePath = str_replace($publicPath, '', $root . DIRECTORY_SEPARATOR . $path);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    return $baseUrl . '/' . ltrim($relativePath, '/');
                }

                return $baseUrl . '/storage/' . ltrim($path, '/');
            }
        }

        return $baseUrl . '/storage/' . ltrim($path, '/');
    }

    /**
     * Format file size for display
     *
     * @param int $bytes File size in bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get validation rules for file upload
     *
     * @param string $type File type (image, pdf, video, document, audio)
     * @param array $options Additional options
     * @return array Validation rules
     */
    public function getFileValidationRules(string $type = 'image', array $options = []): array
    {
        $sizeLimit = $options['sizeLimit'] ?? $this->defaultFileSizeLimits[$type] ?? 5120;
        $sizeLimitKB = $sizeLimit; // Already in KB

        $rules = [];
        
        // Base rule for single file
        $rule = 'required|file';
        
        // Add MIME type validation
        if (isset($this->allowedMimeTypes[$type])) {
            $rule .= '|mimes:' . implode(',', $this->allowedExtensions[$type] ?? []);
        }
        
        // Add size validation (in KB, Laravel expects it in KB)
        $rule .= '|max:' . $sizeLimitKB;
        
        $rules['file'] = $rule;
        
        // For multiple files
        if (isset($options['multiple']) && $options['multiple']) {
            $rules['files.*'] = str_replace('required|', '', $rule);
            $rules['files'] = 'required|array';
            $rules['files.*'] = 'file|' . str_replace('required|file|', '', $rule);
        }
        
        return $rules;
    }

    /**
     * Upload image file (convenience method)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadImage(UploadedFile $file, string $folder = 'uploads/images', array $options = []): array
    {
        return $this->uploadFile($file, 'image', $folder, $options);
    }

    /**
     * Upload PDF file (convenience method)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadPdf(UploadedFile $file, string $folder = 'uploads/documents', array $options = []): array
    {
        return $this->uploadFile($file, 'pdf', $folder, $options);
    }

    /**
     * Upload video file (convenience method)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadVideo(UploadedFile $file, string $folder = 'uploads/videos', array $options = []): array
    {
        return $this->uploadFile($file, 'video', $folder, $options);
    }

    /**
     * Upload document file (convenience method)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadDocument(UploadedFile $file, string $folder = 'uploads/documents', array $options = []): array
    {
        return $this->uploadFile($file, 'document', $folder, $options);
    }

    /**
     * Upload audio file (convenience method)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param array $options
     * @return array
     * @throws ValidationException
     * @throws Exception
     */
    public function uploadAudio(UploadedFile $file, string $folder = 'uploads/audio', array $options = []): array
    {
        return $this->uploadFile($file, 'audio', $folder, $options);
    }
}