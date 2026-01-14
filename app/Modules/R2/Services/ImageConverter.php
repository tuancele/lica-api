<?php

namespace App\Modules\R2\Services;

use Illuminate\Support\Facades\Log;

class ImageConverter
{
    /**
     * Convert image to WebP
     *
     * @param mixed $file UploadedFile object
     * @param int $quality
     * @return array|null ['path' => temp_path, 'name' => new_filename] or null
     */
    public function convertToWebP($file, $quality = 85)
    {
        $debugLog = storage_path('logs/r2_debug.log');
        $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: convertToWebP called\n";
        $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
        @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
        
        if (!function_exists('imagewebp')) {
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: imagewebp() function not available\n\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            return null;
        }

        try {
            // Get file path - try multiple methods
            $filePath = $file->getRealPath();
            if (!$filePath || !file_exists($filePath)) {
                // Fallback: use temporary path
                $filePath = $file->path();
            }
            
            // Another fallback: use getPathname()
            if (!$filePath || !file_exists($filePath)) {
                $filePath = $file->getPathname();
            }
            
            // Last resort: try to get from stream
            if (!$filePath || !file_exists($filePath)) {
                // Save uploaded file to temp location
                $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
                $file->move(sys_get_temp_dir(), basename($tempPath));
                $filePath = sys_get_temp_dir() . '/' . basename($tempPath);
            }
            
            if (!$filePath || !file_exists($filePath)) {
                $errorDetails = [
                    'getRealPath' => $file->getRealPath(),
                    'path' => method_exists($file, 'path') ? $file->path() : 'N/A',
                    'getPathname' => method_exists($file, 'getPathname') ? $file->getPathname() : 'N/A',
                    'isValid' => $file->isValid(),
                    'error' => $file->getError()
                ];
                
                Log::error("ImageConverter: Cannot find file path for: " . $file->getClientOriginalName(), $errorDetails);
                
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Cannot find file path\n";
                $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
                $debugMsg .= "getRealPath: " . ($file->getRealPath() ?: 'null') . "\n";
                $debugMsg .= "path: " . (method_exists($file, 'path') ? ($file->path() ?: 'null') : 'N/A') . "\n";
                $debugMsg .= "getPathname: " . (method_exists($file, 'getPathname') ? ($file->getPathname() ?: 'null') : 'N/A') . "\n";
                $debugMsg .= "isValid: " . ($file->isValid() ? 'Yes' : 'No') . "\n";
                $debugMsg .= "error: " . $file->getError() . "\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                return null;
            }
            
            $mime = $file->getMimeType();
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: File path found\n";
            $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
            $debugMsg .= "Path: $filePath\n";
            $debugMsg .= "MIME: $mime\n";
            $debugMsg .= "Size: $fileSize bytes\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            Log::info("ImageConverter: Processing file - Name: " . $file->getClientOriginalName() . ", MIME: $mime, Path: $filePath, Size: $fileSize bytes");
            
            // If already WebP, return original file info
            if ($mime === 'image/webp') {
                return [
                    'path' => $filePath,
                    'name' => $file->getClientOriginalName(),
                    'is_converted' => false
                ];
            }
            
            $image = null;

            // Read file content first
            $fileContent = @file_get_contents($filePath);
            if ($fileContent === false || strlen($fileContent) === 0) {
                $errorDetails = [
                    'fileExists' => file_exists($filePath),
                    'fileSize' => file_exists($filePath) ? filesize($filePath) : 0,
                    'isReadable' => file_exists($filePath) ? is_readable($filePath) : false,
                    'lastError' => error_get_last(),
                    'fileName' => $file->getClientOriginalName()
                ];
                
                Log::error("ImageConverter: Cannot read file content from: $filePath", $errorDetails);
                
                // Also write to debug log
                $debugLog = storage_path('logs/r2_debug.log');
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Cannot read file content\n";
                $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
                $debugMsg .= "Path: $filePath\n";
                $debugMsg .= "Exists: " . (file_exists($filePath) ? 'Yes' : 'No') . "\n";
                $debugMsg .= "Size: " . (file_exists($filePath) ? filesize($filePath) : 0) . " bytes\n";
                $debugMsg .= "Readable: " . (file_exists($filePath) && is_readable($filePath) ? 'Yes' : 'No') . "\n";
                $debugMsg .= "Last Error: " . json_encode(error_get_last()) . "\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                return null;
            }
            
            $debugLog = storage_path('logs/r2_debug.log');
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: File content read successfully\n";
            $debugMsg .= "Content Size: " . strlen($fileContent) . " bytes\n";
            $debugMsg .= "First Bytes (hex): " . bin2hex(substr($fileContent, 0, 10)) . "\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            Log::info("ImageConverter: File content read successfully", [
                'contentSize' => strlen($fileContent),
                'firstBytes' => bin2hex(substr($fileContent, 0, 10))
            ]);
            
            switch ($mime) {
                case 'image/jpeg':
                case 'image/jpg':
                    $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Attempting JPEG conversion\n";
                    @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                    
                    Log::info("ImageConverter: Attempting JPEG conversion");
                    $image = @imagecreatefromstring($fileContent);
                    if (!$image) {
                        $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: imagecreatefromstring failed, trying imagecreatefromjpeg\n";
                        $debugMsg .= "Last Error: " . json_encode(error_get_last()) . "\n";
                        @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                        
                        Log::warning("ImageConverter: imagecreatefromstring failed, trying imagecreatefromjpeg", [
                            'lastError' => error_get_last()
                        ]);
                        $image = @imagecreatefromjpeg($filePath);
                    }
                    if (!$image) {
                        $errorDetails = [
                            'filePath' => $filePath,
                            'fileSize' => filesize($filePath),
                            'contentSize' => strlen($fileContent),
                            'contentStart' => bin2hex(substr($fileContent, 0, 20)),
                            'lastError' => error_get_last(),
                            'fileExists' => file_exists($filePath),
                            'isReadable' => is_readable($filePath)
                        ];
                        
                        Log::error("ImageConverter: Both imagecreatefromstring and imagecreatefromjpeg failed", $errorDetails);
                        
                        // Also write to debug log
                        $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Both imagecreatefromstring and imagecreatefromjpeg failed\n";
                        $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
                        $debugMsg .= "Path: $filePath\n";
                        $debugMsg .= "Exists: " . (file_exists($filePath) ? 'Yes' : 'No') . "\n";
                        $debugMsg .= "Size: " . filesize($filePath) . " bytes\n";
                        $debugMsg .= "Content Size: " . strlen($fileContent) . " bytes\n";
                        $debugMsg .= "Content Start (hex): " . bin2hex(substr($fileContent, 0, 20)) . "\n";
                        $debugMsg .= "Last Error: " . json_encode(error_get_last()) . "\n\n";
                        @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                        
                        return null;
                    }
                    
                    $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: JPEG image resource created successfully\n";
                    $debugMsg .= "Width: " . imagesx($image) . " px\n";
                    $debugMsg .= "Height: " . imagesy($image) . " px\n";
                    @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                    
                    Log::info("ImageConverter: JPEG image resource created successfully", [
                        'width' => imagesx($image),
                        'height' => imagesy($image)
                    ]);
                    break;
                case 'image/png':
                    Log::info("ImageConverter: Attempting PNG conversion");
                    $image = @imagecreatefromstring($fileContent);
                    if (!$image) {
                        Log::warning("ImageConverter: imagecreatefromstring failed, trying imagecreatefrompng");
                        $image = @imagecreatefrompng($filePath);
                    }
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        Log::info("ImageConverter: PNG image processed successfully");
                    } else {
                        Log::error("ImageConverter: Both imagecreatefromstring and imagecreatefrompng failed", [
                            'filePath' => $filePath,
                            'fileSize' => filesize($filePath),
                            'lastError' => error_get_last()
                        ]);
                    }
                    break;
                case 'image/gif':
                    Log::info("ImageConverter: Attempting GIF conversion");
                    $image = @imagecreatefromstring($fileContent);
                    if (!$image) {
                        Log::warning("ImageConverter: imagecreatefromstring failed, trying imagecreatefromgif");
                        $image = @imagecreatefromgif($filePath);
                    }
                    if ($image) {
                        imagepalettetotruecolor($image);
                        Log::info("ImageConverter: GIF image processed successfully");
                    } else {
                        Log::error("ImageConverter: Both imagecreatefromstring and imagecreatefromgif failed", [
                            'filePath' => $filePath,
                            'fileSize' => filesize($filePath),
                            'lastError' => error_get_last()
                        ]);
                    }
                    break;
                default:
                    Log::warning("ImageConverter: Unsupported MIME type: $mime", [
                        'fileName' => $file->getClientOriginalName()
                    ]);
                    return null;
            }

            if (!$image) {
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Failed to create image resource from file\n";
                $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
                $debugMsg .= "MIME: $mime\n";
                $debugMsg .= "Path: $filePath\n";
                $debugMsg .= "Last Error: " . json_encode(error_get_last()) . "\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                Log::error("ImageConverter: Failed to create image resource from file", [
                    'fileName' => $file->getClientOriginalName(),
                    'mimeType' => $mime,
                    'filePath' => $filePath,
                    'fileSize' => file_exists($filePath) ? filesize($filePath) : 0,
                    'fileExists' => file_exists($filePath),
                    'lastError' => error_get_last()
                ]);
                return null;
            }

            // Create temp file - Use Laravel storage temp directory instead of sys_get_temp_dir()
            // First try Laravel storage temp, then fallback to sys_get_temp_dir()
            $tempDir = storage_path('temp');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }
            
            // If Laravel storage temp is not writable, try sys_get_temp_dir()
            if (!is_writable($tempDir)) {
                $tempDir = sys_get_temp_dir();
            }
            
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Checking temp directory\n";
            $debugMsg .= "Temp Dir: $tempDir\n";
            $debugMsg .= "Writable: " . (is_writable($tempDir) ? 'Yes' : 'No') . "\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            if (!is_writable($tempDir)) {
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Temp directory is not writable\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                Log::error("ImageConverter: Temp directory is not writable", [
                    'tempDir' => $tempDir,
                    'isWritable' => is_writable($tempDir),
                    'storageTemp' => storage_path('temp'),
                    'storageTempWritable' => is_writable(storage_path('temp')),
                    'sysTempDir' => sys_get_temp_dir(),
                    'sysTempDirWritable' => is_writable(sys_get_temp_dir())
                ]);
                @imagedestroy($image);
                return null;
            }
            
            $tempPath = tempnam($tempDir, 'webp_');
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Creating temp file\n";
            $debugMsg .= "Temp Path: " . ($tempPath ?: 'null') . "\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            if (!$tempPath) {
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Failed to create temp file\n";
                $debugMsg .= "Last Error: " . json_encode(error_get_last()) . "\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                Log::error("ImageConverter: Failed to create temp file", [
                    'tempDir' => $tempDir,
                    'isWritable' => is_writable($tempDir),
                    'lastError' => error_get_last()
                ]);
                @imagedestroy($image);
                return null;
            }
            
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: Attempting to save WebP\n";
            $debugMsg .= "Temp Path: $tempPath\n";
            $debugMsg .= "Quality: $quality\n";
            $debugMsg .= "Image Width: " . imagesx($image) . " px\n";
            $debugMsg .= "Image Height: " . imagesy($image) . " px\n";
            $debugMsg .= "Temp Dir Writable: " . (is_writable($tempDir) ? 'Yes' : 'No') . "\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            Log::info("ImageConverter: Attempting to save WebP", [
                'tempPath' => $tempPath,
                'quality' => $quality,
                'imageWidth' => imagesx($image),
                'imageHeight' => imagesy($image),
                'tempDirWritable' => is_writable($tempDir)
            ]);
            
            // Clear any previous errors
            error_clear_last();
            
            $result = @imagewebp($image, $tempPath, $quality);
            $lastError = error_get_last();
            @imagedestroy($image);
            
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: imagewebp() called\n";
            $debugMsg .= "Result: " . ($result ? 'true' : 'false') . "\n";
            $debugMsg .= "File Exists: " . (file_exists($tempPath) ? 'Yes' : 'No') . "\n";
            $debugMsg .= "File Size: " . (file_exists($tempPath) ? filesize($tempPath) : 0) . " bytes\n";
            $debugMsg .= "Last Error: " . json_encode($lastError) . "\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);

            if ($result && file_exists($tempPath)) {
                $webpSize = filesize($tempPath);
                if ($webpSize > 0) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    Log::info("ImageConverter: WebP conversion successful", [
                        'originalName' => $file->getClientOriginalName(),
                        'webpPath' => $tempPath,
                        'webpSize' => $webpSize,
                        'originalSize' => filesize($filePath)
                    ]);
                    return [
                        'path' => $tempPath,
                        'name' => $originalName . '.webp',
                        'is_converted' => true
                    ];
                } else {
                    Log::error("ImageConverter: WebP file created but size is 0", [
                        'tempPath' => $tempPath,
                        'fileExists' => file_exists($tempPath),
                        'fileSize' => filesize($tempPath),
                        'lastError' => $lastError
                    ]);
                    if (file_exists($tempPath)) {
                        @unlink($tempPath);
                    }
                }
            } else {
                $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: imagewebp() failed or file not created\n";
                $debugMsg .= "Result: " . ($result ? 'true' : 'false') . "\n";
                $debugMsg .= "Temp Path: $tempPath\n";
                $debugMsg .= "File Exists: " . (file_exists($tempPath) ? 'Yes' : 'No') . "\n";
                $debugMsg .= "File Size: " . (file_exists($tempPath) ? filesize($tempPath) : 0) . " bytes\n";
                $debugMsg .= "Temp Dir Writable: " . (is_writable($tempDir) ? 'Yes' : 'No') . "\n";
                $debugMsg .= "Last Error: " . json_encode($lastError) . "\n\n";
                @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                
                Log::error("ImageConverter: imagewebp() failed or file not created", [
                    'result' => $result,
                    'tempPath' => $tempPath,
                    'fileExists' => file_exists($tempPath),
                    'fileSize' => file_exists($tempPath) ? filesize($tempPath) : 0,
                    'lastError' => $lastError,
                    'tempDirWritable' => is_writable($tempDir)
                ]);
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } catch (\Exception $e) {
            $debugLog = storage_path('logs/r2_debug.log');
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter: EXCEPTION caught\n";
            $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
            $debugMsg .= "Message: " . $e->getMessage() . "\n";
            $debugMsg .= "File: " . $e->getFile() . "\n";
            $debugMsg .= "Line: " . $e->getLine() . "\n";
            $debugMsg .= "Trace: " . $e->getTraceAsString() . "\n";
            $debugMsg .= "FilePath: " . ($filePath ?? 'N/A') . "\n";
            $debugMsg .= "FileExists: " . (isset($filePath) && file_exists($filePath) ? 'Yes' : 'No') . "\n";
            $debugMsg .= "MimeType: " . ($mime ?? 'N/A') . "\n\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
            
            Log::error('WebP Conversion Failed: ' . $e->getMessage(), [
                'fileName' => $file->getClientOriginalName(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'filePath' => $filePath ?? 'N/A',
                'fileExists' => isset($filePath) && file_exists($filePath),
                'mimeType' => $mime ?? 'N/A'
            ]);
        }
        
        $warningMsg = 'ImageConverter: Returning null - conversion failed or file not supported';
        Log::warning($warningMsg, [
            'fileName' => $file->getClientOriginalName(),
            'mimeType' => $mime ?? 'N/A',
            'filePath' => $filePath ?? 'N/A',
            'fileExists' => isset($filePath) && file_exists($filePath),
            'fileSize' => isset($filePath) && file_exists($filePath) ? filesize($filePath) : 0
        ]);
        
        // Also write to debug log
        if (function_exists('storage_path')) {
            $debugLog = storage_path('logs/r2_debug.log');
            $debugMsg = date('Y-m-d H:i:s') . " - ImageConverter returned null\n";
            $debugMsg .= "File: " . $file->getClientOriginalName() . "\n";
            $debugMsg .= "MIME: " . ($mime ?? 'N/A') . "\n";
            $debugMsg .= "Path: " . ($filePath ?? 'N/A') . "\n";
            $debugMsg .= "Exists: " . (isset($filePath) && file_exists($filePath) ? 'Yes' : 'No') . "\n\n";
            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
        }
        
        return null;
    }
}
