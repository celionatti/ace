<?php

declare(strict_types=1);

namespace Ace\ace;

use Ace\ace\View\View;
use Ace\ace\Illuminate\CSRFGuard;

abstract class Controller
{
    public View $view;

    public function __construct()
    {
        $this->view = new View(BASE_PATH . '/resources/views');
        // $this->view->setCsrfTokenGenerator(new CSRFGuard());
    }

    protected function render(string $template, array $data = [])
    {
        return $this->view->render($template, $data);
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}