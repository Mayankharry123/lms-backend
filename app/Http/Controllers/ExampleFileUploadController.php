<?php
/**
 * ExampleFileUploadController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\ResponseService;
use App\Traits\HandlesFileUploads;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Example controller demonstrating file upload usage
 * You can use this as a reference for implementing file uploads in your controllers
 */
class ExampleFileUploadController extends Controller
{
    use HandlesFileUploads, ValidatesRequests;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * Create a new ExampleFileUploadController instance.
     *
     * @param ResponseService $responseService
     */
    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Upload a single image
     *
     * POST /api/upload/image
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            // Get validation rules for image upload
            $rules = $this->getFileValidationRules('image', [
                'sizeLimit' => 5120, // 5MB in KB
            ]);

            // Validate the request
            $this->validate($request, $rules);

            // Upload the file
            $uploadedFile = $this->uploadImage(
                $request->file('file'),
                'uploads/images', // Folder path
                [
                    'prefix' => 'img_', // Optional: Add prefix to filename
                    // 'disk' => 's3', // Optional: Use S3 or other disk
                ]
            );

            return $this->responseService->success($uploadedFile, 'Image uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload multiple images
     *
     * POST /api/upload/images
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadImages(Request $request): JsonResponse
    {
        try {
            // Get validation rules for multiple images
            $rules = $this->getFileValidationRules('image', [
                'multiple' => true,
                'sizeLimit' => 5120, // 5MB
            ]);

            // Validate the request
            $this->validate($request, $rules);

            // Upload multiple files
            $uploadedFiles = $this->uploadFiles(
                $request->file('files'),
                'image',
                'uploads/images'
            );

            return $this->responseService->success(
                ['files' => $uploadedFiles],
                'Images uploaded successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload a PDF document
     *
     * POST /api/upload/pdf
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadPdf(Request $request): JsonResponse
    {
        try {
            $rules = $this->getFileValidationRules('pdf', [
                'sizeLimit' => 10240, // 10MB
            ]);

            $this->validate($request, $rules);

            $uploadedFile = $this->uploadPdf(
                $request->file('file'),
                'uploads/documents'
            );

            return $this->responseService->success($uploadedFile, 'PDF uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload a video
     *
     * POST /api/upload/video
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        try {
            $rules = $this->getFileValidationRules('video', [
                'sizeLimit' => 51200, // 50MB
            ]);

            $this->validate($request, $rules);

            $uploadedFile = $this->uploadVideo(
                $request->file('file'),
                'uploads/videos'
            );

            return $this->responseService->success($uploadedFile, 'Video uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload a document (Word, Excel, PowerPoint, etc.)
     *
     * POST /api/upload/document
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        try {
            $rules = $this->getFileValidationRules('document', [
                'sizeLimit' => 10240, // 10MB
            ]);

            $this->validate($request, $rules);

            $uploadedFile = $this->uploadDocument(
                $request->file('file'),
                'uploads/documents'
            );

            return $this->responseService->success($uploadedFile, 'Document uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Upload any type of file with custom validation
     *
     * POST /api/upload/file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAnyFile(Request $request): JsonResponse
    {
        try {
            // Custom validation with specific file type
            $fileType = $request->input('file_type', 'image'); // image, pdf, video, document, audio
            
            $rules = $this->getFileValidationRules($fileType, [
                'sizeLimit' => $request->input('max_size', 5120), // Default 5MB
            ]);

            $this->validate($request, [
                'file' => $rules['file'],
                'file_type' => 'required|in:image,pdf,video,document,audio',
                'max_size' => 'nullable|integer|min:1|max:102400', // Max 100MB
            ]);

            // Use the trait's uploadFile method
            $uploadedFile = $this->uploadFile(
                $request->file('file'),
                $fileType,
                "uploads/{$fileType}s"
            );

            return $this->responseService->success($uploadedFile, 'File uploaded successfully');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Delete an uploaded file
     *
     * DELETE /api/upload/file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteUploadedFile(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'file_path' => 'required|string',
            ]);

            // Use the trait's deleteFile method
            $deleted = $this->deleteFile($request->input('file_path'));

            if ($deleted) {
                return $this->responseService->success(null, 'File deleted successfully');
            }

            return $this->responseService->error('Failed to delete file', null, 500);
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get file URL
     *
     * GET /api/upload/file/url
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFileUrl(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'file_path' => 'required|string',
            ]);

            $url = $this->getFileUrl($request->input('file_path'));

            if ($url) {
                return $this->responseService->success(['url' => $url], 'File URL retrieved successfully');
            }

            return $this->responseService->error('File not found', null, 404);
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}

