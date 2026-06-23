<?php

namespace Ace;

class Response
{
    /**
     * Set HTTP response status code
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Redirect to another URL, auto-detecting base subdirectories for local environment
     */
    public function redirect(string $url): void
    {
        // If it's an absolute URL, redirect directly
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            header('Location: ' . $url);
            exit;
        }

        // Prepend base folder for local subdirectory installations (e.g. /mvc/public)
        if (str_starts_with($url, '/')) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $baseDir = dirname($scriptName);
            $baseDir = str_replace('\\', '/', $baseDir);
            
            if ($baseDir !== '/' && !empty($baseDir)) {
                $url = rtrim($baseDir, '/') . $url;
            }
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Return JSON response
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

