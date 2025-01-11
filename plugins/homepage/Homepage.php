<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ========= Home Page Plugin ============
 * ***************************************
 * =======================================
 */

namespace Ace\plugins;

use Celionatti\Ace\Plugins\Plugin;
use Celionatti\Ace\Router\Router;

class Homepage extends Plugin
{
    protected function getName(): string
    {
        return "Homepage Plugin";
    }

    protected function getPath(): string
    {
        return __DIR__;
    }

    protected function getDescription(): string
    {
        return "Provides the homepage functionality.";
    }

    public function initialize()
    {
        Router::add('GET', '/', function () {
            // echo $this->renderHomepage();
            echo $this->view->render("home");
        });
    }

    private function renderHomepage(): string
    {
        return "
            <html>
            <head>
                <title>Homepage</title>
                <link rel='stylesheet' href='/plugins-assets/homepage/css/style.css'>
            </head>
            <body>
                <h1>Welcome to My Website</h1>
                <p>This is the homepage provided by the HomepagePlugin.</p>
                <script src='/plugins-assets/homepage/js/script.js'></script>
            </body>
            </html>
        ";
    }
}