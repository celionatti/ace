<?php

declare(strict_types=1);

/**
 * ================================
 * View Class ===========
 * ================================
 */

namespace Ace\ace\View;

use Ace\ace\Exception\AceException;

class workingView
{
    private string $title = '';
    private string $header = 'Dashboard';
    private array $sections = [];
    private ?string $currentSection = null;
    private string $layout = 'default';
    private array $directives = [];
    private string $baseTemplatePath;
    private string $baseCachePath;
    private const TEMPLATE_EXTENSION = '.php';

    /**
     * Initialize the View class
     *
     * @param string|null $templatesPath Base path for templates
     * @param string|null $cachePath Base path for cache
     */
    public function __construct(?string $templatesPath = null, ?string $cachePath = null)
    {
        // Default paths if not provided
        $this->baseTemplatePath = $templatesPath ?? dirname(__DIR__, 3) . '/resources';
        $this->baseCachePath = $cachePath ?? dirname(__DIR__, 3) . '/storage/cache';

        $this->registerDirectives();
    }

    /**
     * Sets the base path for templates
     */
    public function setTemplatePath(string $path): self
    {
        $this->baseTemplatePath = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Sets the base path for cache
     */
    public function setCachePath(string $path): self
    {
        $this->baseCachePath = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Resolves a template path to its full path
     */
    private function resolvePath(string $path, string $type = 'resources'): string
    {
        // Clean the path of any potential directory traversal
        $path = preg_replace('/\.{2,}/', '', $path);
        $path = str_replace(['\\', '//'], DIRECTORY_SEPARATOR, $path);
        $path = ltrim($path, '/\\');

        // Add extension if not already present
        if (!str_ends_with($path, self::TEMPLATE_EXTENSION)) {
            $path .= self::TEMPLATE_EXTENSION;
        }

        if ($type === 'resources') {
            return $this->baseTemplatePath . DIRECTORY_SEPARATOR . $path;
        } elseif ($type === 'cache') {
            return $this->baseCachePath . DIRECTORY_SEPARATOR . md5($path) . self::TEMPLATE_EXTENSION;
        }

        throw new AceException("Invalid path type: $type");
    }

    /**
     * Sets the layout template to use
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Sets the page title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Gets the page title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Gets the page header
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * Sets the page header
     */
    public function setHeader(string $header): self
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Starts capturing content for a named section
     */
    public function start(string $key): void
    {
        if (empty($key)) {
            throw new AceException("Section key cannot be empty");
        }

        $this->currentSection = $key;
        ob_start();
    }

    /**
     * Ends capturing content for the current section
     */
    public function end(): void
    {
        if ($this->currentSection === null) {
            throw new AceException("No active section to end");
        }

        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Outputs the content of a section
     */
    public function content(string $key): void
    {
        echo $this->sections[$key] ?? '';
    }

    /**
     * Includes a partial template
     */
    public function partial(string $path, array $params = []): void
    {
        $fullPath = $this->resolvePath('partials' . DIRECTORY_SEPARATOR . $path);

        if (!file_exists($fullPath)) {
            throw new AceException("Partial view not found: $path", 404);
        }

        extract($params);
        include $fullPath;
    }

    /**
     * Renders a template with optional parameters
     */
    public function render(string $path, array $params = [], bool $returnOutput = false): ?string
    {
        try {
            $output = $this->renderTemplate($path, $params);

            if ($returnOutput) {
                return $output;
            }

            echo $output;
            return null;
        } catch (AceException $e) {
            if ($returnOutput) {
                throw $e;
            }

            echo 'Rendering Error: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Renders data as JSON and sets appropriate headers
     */
    public function renderJson(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Compiles and renders a template with its layout
     */
    private function renderTemplate(string $path, array $params = []): string
    {
        // If path is empty, just return any captured sections
        if (empty($path)) {
            return '';
        }

        $viewPath = $this->resolvePath($path);
        $layoutPath = $this->resolvePath('layouts' . DIRECTORY_SEPARATOR . $this->layout);

        if (!file_exists($viewPath)) {
            throw new AceException("View not found: $path", 404);
        }

        if (!file_exists($layoutPath)) {
            throw new AceException("Layout not found: {$this->layout}", 404);
        }

        // Extract parameters into variables
        extract($params);

        // Render the view content
        $viewContent = file_get_contents($viewPath);
        $compiledView = $this->compileTemplate($viewContent);

        // Capture the view output
        ob_start();
        try {
            eval('?>' . $compiledView);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new AceException("Error rendering view template: " . $e->getMessage());
        }
        $viewOutput = ob_get_clean();

        // Render the layout with sections
        $layoutContent = file_get_contents($layoutPath);
        $compiledLayout = $this->compileTemplate($layoutContent);

        // Capture the layout output
        ob_start();
        try {
            eval('?>' . $compiledLayout);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new AceException("Error rendering layout template: " . $e->getMessage());
        }
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Compiles a template string by processing directives and expressions
     */
    private function compileTemplate(string $template): string
    {
        // Process directives first
        foreach ($this->directives as $pattern => $callback) {
            $template = $this->compileDirectives($template, $pattern, $callback);
        }

        // Process {{ }} expressions
        $template = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function($matches) {
            return '<?php echo htmlspecialchars(' . $matches[1] . ', ENT_QUOTES, \'UTF-8\'); ?>';
        }, $template);

        // Process {!! !!} for unescaped output
        $template = preg_replace_callback('/\{!!\s*(.+?)\s*!!\}/', function($matches) {
            return '<?php echo ' . $matches[1] . '; ?>';
        }, $template);

        return $template;
    }

    /**
     * Compiles a specific directive in the template
     */
    private function compileDirectives(string $template, string $pattern, callable $callback): string
    {
        // Determine how many required parameters the callback expects
        $ref = new \ReflectionFunction(\Closure::fromCallable($callback));
        $requiredParams = $ref->getNumberOfRequiredParameters();

        if ($requiredParams > 0) {
            // For directives with parameters (e.g., @if, @section), capture the expression inside parentheses.
            $regex = '/' . preg_quote($pattern, '/') . '\s*\((.*?)\)/';
            return preg_replace_callback(
                $regex,
                function($matches) use ($callback) {
                    return $callback($matches[1]);
                },
                $template
            );
        } else {
            // For directives without parameters (e.g., @else), simply replace the directive.
            $regex = '/' . preg_quote($pattern, '/') . '/';
            return preg_replace_callback(
                $regex,
                function() use ($callback) {
                    return $callback();
                },
                $template
            );
        }
    }

    /**
     * Ensures a directory exists
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new AceException("Failed to create directory: $path");
            }
        }
    }

    /**
     * Registers all template directives
     */
    private function registerDirectives(): void
    {
        // Control structures
        $this->directives['@if'] = fn($expr) => '<?php if(' . $expr . '): ?>';
        $this->directives['@else'] = fn() => '<?php else: ?>';
        $this->directives['@elseif'] = fn($expr) => '<?php elseif(' . $expr . '): ?>';
        $this->directives['@endif'] = fn() => '<?php endif; ?>';

        // Loops
        $this->directives['@foreach'] = fn($expr) => '<?php foreach(' . $expr . '): ?>';
        $this->directives['@endforeach'] = fn() => '<?php endforeach; ?>';
        $this->directives['@for'] = fn($expr) => '<?php for(' . $expr . '): ?>';
        $this->directives['@endfor'] = fn() => '<?php endfor; ?>';
        $this->directives['@while'] = fn($expr) => '<?php while(' . $expr . '): ?>';
        $this->directives['@endwhile'] = fn() => '<?php endwhile; ?>';

        // Switch statements
        $this->directives['@switch'] = fn($expr) => '<?php switch(' . $expr . '): ?>';
        $this->directives['@case'] = fn($expr) => '<?php case ' . $expr . ': ?>';
        $this->directives['@break'] = fn() => '<?php break; ?>';
        $this->directives['@default'] = fn() => '<?php default: ?>';
        $this->directives['@endswitch'] = fn() => '<?php endswitch; ?>';

        // Template inheritance
        $this->directives['@extends'] = fn($expr) => '<?php $this->setLayout(' . $expr . '); ?>';
        $this->directives['@section'] = fn($expr) => '<?php $this->start(' . $expr . '); ?>';
        $this->directives['@endsection'] = fn() => '<?php $this->end(); ?>';
        $this->directives['@yield'] = fn($expr) => '<?php $this->content(' . $expr . '); ?>';

        // Authentication shortcuts
        $this->directives['@auth'] = fn() => '<?php if(function_exists("auth") && auth()->check()): ?>';
        $this->directives['@endauth'] = fn() => '<?php endif; ?>';
        $this->directives['@guest'] = fn() => '<?php if(function_exists("auth") && auth()->guest()): ?>';
        $this->directives['@endguest'] = fn() => '<?php endif; ?>';

        // Conditionals
        $this->directives['@isset'] = fn($expr) => '<?php if(isset(' . $expr . ')): ?>';
        $this->directives['@endisset'] = fn() => '<?php endif; ?>';
        $this->directives['@empty'] = fn($expr) => '<?php if(empty(' . $expr . ')): ?>';
        $this->directives['@endempty'] = fn() => '<?php endif; ?>';

        // CSRF protection
        $this->directives['@csrf'] = fn() => '<?php if(function_exists("csrf_token")): ?><input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>"><?php endif; ?>';

        // Include directive
        $this->directives['@include'] = fn($expr) => '<?php $this->partial(' . $expr . '); ?>';
    }
}