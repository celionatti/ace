<?php

declare(strict_types=1);

/**
 * ================================
 * View Class =====================
 * ================================
 */

namespace Ace\ace\View;

use Ace\ace\Exception\AceException;

class SimpleView
{
    private string $viewsPath;
    private string $cachePath;
    private array $globals = [];
    private array $sections = [];
    private array $sectionStack = [];
    private ?string $parentView = null;

    public function __construct(string $viewsPath, string $cachePath = '')
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = $cachePath ? rtrim($cachePath, '/') : sys_get_temp_dir();
    }

    public function render(string $template, array $data = []): string
    {
        $templateFile = "{$this->viewsPath}/{$template}.ace.php";

        if (!file_exists($templateFile)) {
            throw new AceException("Template not found: {$template}");
        }

        try {
            extract(array_merge($this->globals, $data));
            ob_start();
            include $this->compile($templateFile);
            $content = ob_get_clean();

            // If there's a parent layout, render it AFTER capturing sections
            if ($this->parentView) {
                $parent = $this->parentView;
                $this->parentView = null;
                return $this->render($parent, $data);
            }

            return $content;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new AceException("Rendering failed: {$e->getMessage()}");
        }
    }

    public function addGlobal(string $key, $value): void
    {
        $this->globals[$key] = $value;
    }

    private function compile(string $templatePath): string
    {
        $cacheFile = $this->cachePath . '/' . md5($templatePath) . '.php';

        if (!file_exists($cacheFile) || filemtime($templatePath) > filemtime($cacheFile)) {
            $content = file_get_contents($templatePath);
            $compiled = $this->compileDirectives($content);
            file_put_contents($cacheFile, $compiled);
        }

        return $cacheFile;
    }

    private function compileDirectives(string $content): string
    {
        $replacements = [
            '/\{\{\s*(.+?)\s*\}\}/' => '<?= htmlspecialchars($1, ENT_QUOTES) ?>',
            '/@if\s*\((.*?)\)/' => '<?php if ($1): ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@foreach\s*\((.*?)\)/' => '<?php foreach ($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@for\s*\((.*?)\)/' => '<?php for ($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@extends\([\'"](.+?)[\'"]\)/' => '<?php $this->extend("$1"); ?>',
            '/@section\([\'"](.+?)[\'"]\)/' => '<?php $this->startSection("$1"); ?>',
            '/@endsection/' => '<?php $this->endSection(); ?>',
            '/@yield\([\'"](.+?)[\'"]\)/' => '<?= $this->yieldSection("$1") ?>',
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function extend(string $view): void
    {
        $this->parentView = $view;
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        if (!$name) {
            throw new AceException("No active section to end.");
        }

        $this->sections[$name] = ob_get_clean();
    }

    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }
}
