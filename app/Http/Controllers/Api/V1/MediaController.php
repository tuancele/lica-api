<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\R2\Services\ImageConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload image to Cloudflare R2 with WebP optimization.
     *
     * POST /api/v1/media/upload
     *
     * Request:
     * - file: required|image (jpeg,png,jpg,gif,webp) max 10MB
     * - folder: optional, default "image"
     * - convert_webp: optional bool, default true
     * - quality: optional int, default 85
     */
    public function upload(Request $request): JsonResponse
    {
        $logId = uniqid('MEDIA-R2-', true);

        Log::info("Media Upload [$logId]: Request received", [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $request->validate([
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'folder' => 'nullable|string|max:100',
                'convert_webp' => 'nullable',
                'quality' => 'nullable|integer|min:10|max:100',
            ]);

            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                Log::warning("Media Upload [$logId]: Invalid file upload");
                return response()->json([
                    'success' => false,
                    'message' => 'File không hợp lệ, vui lòng thử lại.',
                ], 400);
            }

            $folder = $request->input('folder', 'image');
            $convertWebP = filter_var($request->input('convert_webp', true), FILTER_VALIDATE_BOOLEAN);
            $quality = (int) $request->input('quality', 85);

            Log::info("Media Upload [$logId]: Params", [
                'folder' => $folder,
                'convert_webp' => $convertWebP,
                'quality' => $quality,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            // Read original content
            $content = $file->get();
            if ($content === false || $content === null || $content === '') {
                Log::error("Media Upload [$logId]: Empty file content");
                return response()->json([
                    'success' => false,
                    'message' => 'Không đọc được nội dung file, vui lòng thử lại.',
                ], 500);
            }

            $mimeType = $file->getMimeType();
            $isAlreadyWebP = ($mimeType === 'image/webp');
            $converter = new ImageConverter();
            $tempFiles = [];

            if ($convertWebP) {
                try {
                    if ($isAlreadyWebP) {
                        Log::info("Media Upload [$logId]: File is already WebP, skip conversion");
                    } else {
                        Log::info("Media Upload [$logId]: Converting to WebP", [
                            'mime' => $mimeType,
                            'quality' => $quality,
                        ]);

                        $webpResult = $converter->convertToWebP($file, $quality);

                        if ($webpResult && !empty($webpResult['is_converted']) && !empty($webpResult['path'])) {
                            $webpPath = $webpResult['path'];
                            if (file_exists($webpPath)) {
                                $newContent = @file_get_contents($webpPath);
                                if ($newContent !== false && strlen($newContent) > 0) {
                                    $content = $newContent;
                                    $tempFiles[] = $webpPath;
                                    Log::info("Media Upload [$logId]: WebP conversion success", [
                                        'size' => strlen($content),
                                    ]);
                                } else {
                                    Log::error("Media Upload [$logId]: WebP file empty or unreadable", [
                                        'webpPath' => $webpPath,
                                    ]);
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Chuyển đổi WebP thất bại, vui lòng thử lại.',
                                    ], 500);
                                }
                            } else {
                                Log::error("Media Upload [$logId]: WebP file not found", [
                                    'webpPath' => $webpPath,
                                ]);
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Chuyển đổi WebP thất bại, vui lòng thử lại.',
                                ], 500);
                            }
                        } elseif ($webpResult && empty($webpResult['is_converted'])) {
                            Log::info("Media Upload [$logId]: ImageConverter reports already WebP, keep original content");
                        } else {
                            Log::error("Media Upload [$logId]: ImageConverter returned null");
                            return response()->json([
                                'success' => false,
                                'message' => 'Chuyển đổi WebP thất bại, vui lòng thử lại.',
                            ], 500);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Media Upload [$logId]: WebP conversion exception: " . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Lỗi khi tối ưu ảnh, vui lòng thử lại.',
                    ], 500);
                }
            }

            // Build R2 path
            $relativeDir = trim($folder, '/');
            $relativePath = $relativeDir . '/' . date('Y/m/d');

            $contentHash = substr(md5($content), 0, 8);
            $dateStr = date('Ymd');
            $randomStr = Str::random(8);
            $fileName = $contentHash . '_' . $dateStr . '_' . $randomStr . '.webp';

            $fullPath = $relativePath . '/' . $fileName;

            Log::info("Media Upload [$logId]: Storing to R2", [
                'path' => $fullPath,
                'size' => strlen($content),
            ]);

            $stored = Storage::disk('r2')->put($fullPath, $content);

            // Clean temp files
            foreach ($tempFiles as $tmp) {
                @unlink($tmp);
            }

            if (!$stored) {
                Log::error("Media Upload [$logId]: Storage::put returned false", [
                    'path' => $fullPath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Upload thất bại, vui lòng thử lại.',
                ], 500);
            }

            $url = Storage::disk('r2')->url($fullPath);
            if (empty($url)) {
                $r2Url = config('filesystems.disks.r2.url');
                if (!empty($r2Url)) {
                    $url = rtrim($r2Url, '/') . '/' . $fullPath;
                }
            }

            Log::info("Media Upload [$logId]: Success", [
                'url' => $url,
                'path' => $fullPath,
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'filename' => $fileName,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Media Upload [$logId]: Validation error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Du lieu khong hop le: ' . implode(', ', $e->errors()),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Media Upload [$logId]: Critical error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Loi he thong: ' . $e->getMessage(),
            ], 500);
        }
    }
}

