<?php

declare(strict_types=1);

/**
 * Framework: ACE Framework
 * Author: Celio Natti
 * Year:   2024
 * Version: 1.0.0
 */

namespace App\Core;

class View
{
    protected $twig;

    public function __construct()
    {
        $loader = new \Twig\Loader\FilesystemLoader('../resources/views');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => '../storage/cache/views',
            'debug' => true,
        ]);
    }

    public function render($template, $data = [])
    {
        echo $this->twig->render($template, $data);
    }
}
