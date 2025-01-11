<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * ============= View Class ==============
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\View;

use Celionatti\Ace\Hookmanager\Hook;

class View
{
    private string $viewPath;

    public function __construct(string $viewPath = "", $template = "views")
    {
        $this->viewPath = rtrim($viewPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;
    }

    /**
     * Render a view template.
     *
     * @param string $view - The name of the view file (without extension).
     * @param array $data - Data to pass to the view.
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        // Apply a filter to modify view data before rendering
        $data = Hook::applyFilter("view_data_{$view}", $data);

        // Capture the output of the view
        ob_start();

        extract($data); // Extract variables for use in the view
        $viewFile = $this->viewPath . $view . '.php';

        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "View not found: {$viewFile}";
        }

        // Allow plugins to modify the final output
        $output = ob_get_clean();
        return Hook::applyFilter("view_output_{$view}", $output, $data);
    }

    /**
     * Render a partial view.
     *
     * @param string $partial - The name of the partial view file.
     * @param array $data - Data to pass to the partial.
     */
    public function partial(string $partial, array $data = []): void
    {
        echo $this->render($partial, $data);
    }
}