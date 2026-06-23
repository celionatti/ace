<?php

namespace Ace;

use Exception;

class UploadService
{
    /**
     * Handle safe file uploading
     * 
     * @param array $file $_FILES['input_name'] array
     * @param string $destinationDir Folder path to store upload
     * @param array $allowedExtensions File extensions to restrict (e.g. ['jpg', 'png'])
     * @param int $maxSize Size limit in bytes (default 5MB)
     * @return array Contains 'success' (bool) and either 'filepath'/'filename' or 'error' message
     */
    public function uploadFile(array $file, string $destinationDir, array $allowedExtensions = [], int $maxSize = 5242880): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds limit of ' . ($maxSize / 1024 / 1024) . 'MB.'
            ];
        }

        $filename = $file['name'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)
            ];
        }

        // Generate unique cryptographically secure name
        $uniqueName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $destinationPath = rtrim($destinationDir, '/\\') . '/' . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return [
                'success' => true,
                'filename' => $uniqueName,
                'filepath' => $destinationPath
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to save uploaded file to disk.'
        ];
    }

    /**
     * Resize image maintaining aspect ratio and alpha transparency using GD Library
     */
    public function resizeImage(string $sourcePath, string $destinationPath, int $targetWidth, int $targetHeight, bool $keepAspectRatio = true): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        list($originalWidth, $originalHeight, $imageType) = $imageInfo;

        // Load image base on MIME type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false; // Type not supported
        }

        if (!$sourceImage) {
            return false;
        }

        $newWidth = $targetWidth;
        $newHeight = $targetHeight;

        // Recalculate dimensions to fit aspect ratios
        if ($keepAspectRatio) {
            $aspectRatio = $originalWidth / $originalHeight;
            if ($newWidth / $newHeight > $aspectRatio) {
                $newWidth = (int)round($newHeight * $aspectRatio);
            } else {
                $newHeight = (int)round($newWidth / $aspectRatio);
            }
        }

        // Create empty true color canvas
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Maintain alpha transparency layers for PNG, WEBP, and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        } elseif ($imageType === IMAGETYPE_GIF) {
            $transparentIndex = imagecolortransparent($sourceImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                $transparency = imagecolorallocatealpha(
                    $resizedImage,
                    $transparentColor['red'],
                    $transparentColor['green'],
                    $transparentColor['blue'],
                    127
                );
                imagefill($resizedImage, 0, 0, $transparency);
                imagecolortransparent($resizedImage, $transparency);
            }
        }

        // Resample copying original to destination size
        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Ensure output folder exists
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Save image to output file path
        $result = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($resizedImage, $destinationPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($resizedImage, $destinationPath, 6);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($resizedImage, $destinationPath);
                break;
            case IMAGETYPE_WEBP:
                $result = imagewebp($resizedImage, $destinationPath, 80);
                break;
        }

        // Release references from memory
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $result;
    }

    /**
     * Map PHP upload errors to user readable string logs
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'An unknown upload error occurred.';
        }
    }
}

