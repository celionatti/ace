<?php

namespace App\Controllers;

use Ace\Application;
use Ace\Controller;
use Ace\Request;
use Ace\UploadService;
use App\Middlewares\CsrfMiddleware;

class UploadController extends Controller
{
    public function __construct()
    {
        // Apply CSRF validation on file upload POST action
        $this->registerMiddleware(new CsrfMiddleware(['upload']));
    }

    /**
     * Render image upload form view
     */
    public function uploadView(Request $request): string
    {
        return $this->render('upload', [
            'originalUrl' => null,
            'thumbnailUrl' => null,
            'error' => null
        ]);
    }

    /**
     * Process image upload and resizing
     */
    public function upload(Request $request): string
    {
        $body = $request->getBody();
        $targetWidth = (int)($body['width'] ?? 300);
        $targetHeight = (int)($body['height'] ?? 300);
        
        $originalUrl = null;
        $thumbnailUrl = null;
        $error = null;

        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Please select a valid image file.';
        } else {
            try {
                $uploader = new UploadService();
                
                // Set folders inside public directory
                $originalDir = Application::$ROOT_DIR . '/public/uploads/original';
                $thumbnailDir = Application::$ROOT_DIR . '/public/uploads/thumbnails';
                
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                // Perform safe upload
                $uploadResult = $uploader->uploadFile($_FILES['image'], $originalDir, $allowed);

                if ($uploadResult['success']) {
                    $filename = $uploadResult['filename'];
                    $originalFilePath = $uploadResult['filepath'];
                    $thumbnailFilePath = $thumbnailDir . '/' . $filename;

                    // Perform resize using GD
                    $resized = $uploader->resizeImage($originalFilePath, $thumbnailFilePath, $targetWidth, $targetHeight, true);

                    if ($resized) {
                        // Construct public relative paths
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                        $originalUrl = $basePath . '/uploads/original/' . $filename;
                        $thumbnailUrl = $basePath . '/uploads/thumbnails/' . $filename;
                        
                        Application::$app->session->setFlash('success', 'Image uploaded and resized successfully!');
                    } else {
                        $error = 'Failed to resize the uploaded image.';
                    }
                } else {
                    $error = $uploadResult['error'];
                }
            } catch (\Exception $e) {
                $error = 'System Error: ' . $e->getMessage();
            }
        }

        return $this->render('upload', [
            'originalUrl' => $originalUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'error' => $error,
            'width' => $targetWidth,
            'height' => $targetHeight
        ]);
    }
}

