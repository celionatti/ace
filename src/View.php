<?php

namespace Ace;

use Exception;

class View
{
    private string $viewsDir;
    private string $cacheDir;
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;
    private array $componentStack = [];

    public function __construct(string $viewsDir, string $cacheDir)
    {
        $this->viewsDir = $viewsDir;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Render view template parsing Blade-like syntax and compiling standard PHP
     * 
     * @param string $view View name (e.g. 'home', 'errors/404')
     * @param array $params Parameter keys and values to extract
     * @return string Final compiled HTML output
     */
    public function render(string $view, array $params = []): string
    {
        $isDev = ($_ENV['APP_ENV'] ?? 'development') === 'development';
        // Run garbage collection probabilistically (1% chance) only in development
        if ($isDev && rand(1, 100) === 1) {
            $this->garbageCollectCache();
        }

        // Reset state between renders
        $this->sections = [];
        $this->currentSection = null;
        $this->layout = null;

        $viewFile = $this->viewsDir . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View template '$view' not found.", 404);
        }

        // Compile view template to cached PHP file
        $compiledViewFile = $this->compile($viewFile);

        // Extract params to local variables
        extract($params);

        // Buffer and execute compiled view
        ob_start();
        include $compiledViewFile;
        $viewContent = ob_get_clean();

        // If layout was set via `@extends('layout')` directive inside the view file
        if ($this->layout) {
            $layoutFile = $this->viewsDir . '/' . $this->layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new Exception("Layout '{$this->layout}' specified in '$view' not found.", 500);
            }

            // Compile the layout template
            $compiledLayoutFile = $this->compile($layoutFile);

            // If view has no sections, treat whole viewContent as default 'content' section
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $viewContent;
            }

            // Buffer and render layout
            ob_start();
            include $compiledLayoutFile;
            $layoutContent = ob_get_clean();

            return $layoutContent;
        }

        return $viewContent;
    }

    /**
     * Set layout template (called via compiled @extends directive)
     */
    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Start recording output buffer for a named section (called via compiled @section directive)
     */
    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Close output buffer and record content for current section (called via compiled @endsection directive)
     */
    public function endSection(): void
    {
        if (!$this->currentSection) {
            return;
        }
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Output a compiled section (called via compiled @yield directive)
     */
    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Include a sub-view (called via compiled @include directive)
     */
    public function include(string $view, array $params = []): string
    {
        $viewFile = $this->viewsDir . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("Included view template '$view' not found.", 404);
        }

        $compiledViewFile = $this->compile($viewFile);
        return $this->renderSubView($compiledViewFile, $params);
    }

    /**
     * Render compiled sub-view file inside an isolated scope
     */
    private function renderSubView(string $compiledFile, array $params): string
    {
        extract($params);
        ob_start();
        include $compiledFile;
        return ob_get_clean();
    }

    /**
     * Start a component layout section (called via compiled @component directive)
     */
    public function startComponent(string $view, array $params = []): void
    {
        $this->componentStack[] = [
            'view' => $view,
            'params' => $params,
            'slots' => []
        ];
        ob_start();
    }

    /**
     * End a component layout, gathering all slot contents and rendering the component view
     */
    public function endComponent(): string
    {
        if (empty($this->componentStack)) {
            return '';
        }

        $component = array_pop($this->componentStack);
        $slotContent = ob_get_clean();

        $params = $component['params'];
        $params['slot'] = $slotContent;

        foreach ($component['slots'] as $name => $content) {
            $params[$name] = $content;
        }

        return $this->include($component['view'], $params);
    }

    /**
     * Start recording output buffer for a named slot (called via compiled @slot directive)
     */
    public function startSlot(string $name): void
    {
        ob_start();
        if (!empty($this->componentStack)) {
            $lastIndex = count($this->componentStack) - 1;
            $this->componentStack[$lastIndex]['active_slot'] = $name;
        }
    }

    /**
     * End recording output buffer for current slot (called via compiled @endslot directive)
     */
    public function endSlot(): void
    {
        if (empty($this->componentStack)) {
            ob_end_clean();
            return;
        }

        $lastIndex = count($this->componentStack) - 1;
        $activeSlot = $this->componentStack[$lastIndex]['active_slot'] ?? null;

        if ($activeSlot) {
            $this->componentStack[$lastIndex]['slots'][$activeSlot] = ob_get_clean();
            unset($this->componentStack[$lastIndex]['active_slot']);
        } else {
            ob_end_clean();
        }
    }

    /**
     * Output a URL safely — keeps & intact for query strings,
     * but blocks dangerous schemes (javascript:, vbscript:, data:)
     */
    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        // Block dangerous URI schemes
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if (in_array($scheme, ['javascript', 'vbscript', 'data'])) {
            return '';
        }
        // Sanitize: remove illegal URL chars but keep & intact
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Compile template file if modified, returning path to cached compiled script
     */
    private function compile(string $filePath): string
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $cacheFile = $this->cacheDir . '/' . md5($filePath) . '.php';

        $isDev = ($_ENV['APP_ENV'] ?? 'development') === 'development';
        $shouldCompile = !file_exists($cacheFile);

        // Only check file modification times in development environment
        if ($isDev && !$shouldCompile) {
            $shouldCompile = filemtime($filePath) > filemtime($cacheFile);
        }

        if ($shouldCompile) {
            $content = file_get_contents($filePath);
            $sourceComment = "<?php // Source: " . str_replace('\\', '/', $filePath) . " ?>\n";
            $compiledContent = $sourceComment . $this->compileString($content);
            file_put_contents($cacheFile, $compiledContent);
        }

        return $cacheFile;
    }

    /**
     * Garbage collect compiled views whose source templates no longer exist
     */
    public function garbageCollectCache(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }

        $files = glob($this->cacheDir . '/*.php');
        foreach ($files as $file) {
            if (file_exists($file)) {
                $handle = @fopen($file, 'r');
                if ($handle) {
                    $firstLine = fgets($handle);
                    fclose($handle);
                    if (preg_match('/\/\/ Source: (.+?) \?>/', $firstLine, $matches)) {
                        $sourcePath = $matches[1];
                        if (!file_exists($sourcePath)) {
                            @unlink($file);
                        }
                    }
                }
            }
        }
    }

    /**
     * Compiles template string syntax into standard executable PHP
     */
    public function compileString(string $content): string
    {
        // Escape @@ and @{{
        $content = str_replace('@@', '___AT_ESCAPE___', $content);
        $content = str_replace('@{{', '___CURLY_ESCAPE___', $content);

        // 1. Convert {{ $var }} to htmlspecialchars escaped echo output
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars((string)($1 ?? ""), ENT_QUOTES, "UTF-8"); ?>', $content);

        // 2. Convert {!! $var !!} or {{{ $var }}} to raw unescaped echo output
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);

        // 2b. Convert @url($var) to XSS-safe URL output (keeps & intact, blocks dangerous schemes)
        // Uses balanced parentheses regex to support nested calls like @url(route('home'))
        $content = preg_replace('/@url\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php echo \Ace\View::safeUrl((string)($1 ?? "")); ?>', $content);

        // 3. Convert @extends('layout') -> $this->layout('layout')
        $content = preg_replace('/@extends\(\'(.*?)\'\)/', '<?php $this->layout(\'$1\'); ?>', $content);

        // 4. Convert @section('name') -> $this->startSection('name')
        $content = preg_replace('/@section\(\'(.*?)\'\)/', '<?php $this->startSection(\'$1\'); ?>', $content);

        // 5. Convert @endsection -> $this->endSection()
        $content = str_replace('@endsection', '<?php $this->endSection(); ?>', $content);

        // 6. Convert @yield('name') -> echo $this->yieldSection('name')
        $content = preg_replace('/@yield\(\'(.*?)\'\)/', '<?php echo $this->yieldSection(\'$1\'); ?>', $content);

        // 7. Compiling conditional control structures (balanced parens regex for nested calls)
        $content = preg_replace('/@if\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php if($1): ?>', $content);
        $content = preg_replace('/@elseif\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php elseif($1): ?>', $content);
        $content = str_replace('@endif', '<?php endif; ?>', $content);
        $content = preg_replace('/@else(?!if)/', '<?php else: ?>', $content);

        // 8. Compiling loops (balanced parens regex for nested calls)
        $content = preg_replace('/@foreach\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php foreach($1): ?>', $content);
        $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);

        // 9. Compiling Auth tags (longer @endauth/@endguest first to prevent substring match)
        $content = str_replace('@endauth', '<?php endif; ?>', $content);
        $content = str_replace('@endguest', '<?php endif; ?>', $content);
        $content = str_replace('@auth', '<?php if(!\Ace\Application::isGuest()): ?>', $content);
        $content = str_replace('@guest', '<?php if(\Ace\Application::isGuest()): ?>', $content);

        // 9b. Compiling CSRF tag
        $content = str_replace('@csrf', '<?php echo csrf_field(); ?>', $content);

        // 9c. Compiling Isset tags
        $content = preg_replace('/@isset\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php if(isset($1)): ?>', $content);
        $content = str_replace('@endisset', '<?php endif; ?>', $content);

        // 9d. Compiling Empty tags
        $content = preg_replace('/@empty\(((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*)\)/', '<?php if(empty($1)): ?>', $content);
        $content = str_replace('@endempty', '<?php endif; ?>', $content);

        // 9e. Compiling Session tags
        $content = preg_replace_callback(
            '/@session\(\s*(["\'])(.*?)\1\s*\)/s',
            function ($matches) {
                return "<?php if(\$value = \Ace\Application::\$app->session->getFlash('{$matches[2]}')): ?>";
            },
            $content
        );
        $content = str_replace('@endsession', '<?php endif; ?>', $content);

        // 10. Compiling Includes (balanced parens regex to support nested expressions in params)
        $content = preg_replace_callback(
            '/@include\(\s*(["\'])(.*?)\1\s*(?:,\s*((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*))?\s*\)/s',
            function ($matches) {
                $view = $matches[2];
                $extraParams = trim($matches[3] ?? '');
                if ($extraParams) {
                    return "<?php echo \$this->include('{$view}', array_merge(get_defined_vars(), {$extraParams})); ?>";
                }
                return "<?php echo \$this->include('{$view}', get_defined_vars()); ?>";
            },
            $content
        );

        // 11. Compiling Components and Slots (balanced parens regex to support (object) casts etc.)
        $content = preg_replace_callback(
            '/@component\(\s*(["\'])(.*?)\1\s*(?:,\s*((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*))?\s*\)/s',
            function ($matches) {
                $view = $matches[2];
                $extraParams = trim($matches[3] ?? '');
                if ($extraParams) {
                    return "<?php \$this->startComponent('{$view}', array_merge(get_defined_vars(), {$extraParams})); ?>";
                }
                return "<?php \$this->startComponent('{$view}', get_defined_vars()); ?>";
            },
            $content
        );
        $content = str_replace('@endcomponent', '<?php echo $this->endComponent(); ?>', $content);

        $content = preg_replace('/@slot\(\s*(["\'])(.*?)\1\s*\)/s', '<?php $this->startSlot(\'$2\'); ?>', $content);
        $content = str_replace('@endslot', '<?php $this->endSlot(); ?>', $content);

        // Restore escaped @@ and @{{
        $content = str_replace('___AT_ESCAPE___', '@', $content);
        $content = str_replace('___CURLY_ESCAPE___', '{{', $content);

        return $content;
    }
}

