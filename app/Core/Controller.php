<?php

declare(strict_types=1);

/**
 * Framework: ACE Framework
 * Author: Celio Natti
 * Year:   2024
 * Version: 1.0.0
 */

namespace App\Core;

class Controller
{
    protected $view;

    public function __construct()
    {
        $this->view = new View();
    }

    public function render($template, $data = [])
    {
        $this->view->render($template, $data);
    }
}
