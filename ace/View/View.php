<?php

declare(strict_types=1);

/**
 * ================================
 * View Class =====================
 * ================================
 */

namespace Ace\ace\View;

use Ace\ace\Exception\AceException;

class View
{
    private string $viewsPath;
    private string $cachePath;
    private bool $cacheEnabled;
    private array $globals = [];
    private array $sections = [];
    private array $sectionStack = [];
    private array $componentStack = [];
    private array $slots = [];
    private ?string $parentView = null;
    private $translator = null;
    private $csrfTokenGenerator = null;
    private array $customDirectives = [];
    private bool $debug;
    private array $compilers = [
        'comments',
        'directives',
        'unescaped',
        'php',
    ];

    public function __construct(string $viewsPath, string $cachePath = '', bool $cacheEnabled = true, bool $debug = false) {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = $cachePath ? rtrim($cachePath, '/') : sys_get_temp_dir();
        $this->cacheEnabled = $cacheEnabled;
        $this->debug = $debug;

        if (!is_writable($this->cachePath)) {
            throw new AceException("Cache directory is not writable: {$this->cachePath}");
        }
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

            if ($this->parentView) {
                $parent = $this->parentView;
                $this->parentView = null;
                return $this->render($parent, $data);
            }

            return $content;
        } catch (Throwable $e) {
            ob_end_clean();
            throw new AceException($this->formatError($e, $templateFile), 0, $e);
        }
    }

    public function addGlobal(string $key, $value): void
    {
        $this->globals[$key] = $value;
    }

    public function setTranslator(callable $translator): void
    {
        $this->translator = $translator;
    }

    public function setCsrfTokenGenerator(callable $generator): void
    {
        $this->csrfTokenGenerator = $generator;
    }

    public function registerDirective(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    private function compile(string $templatePath): string
    {
        $cacheFile = $this->cachePath.'/'.md5($templatePath).'.php';

        if (!$this->cacheEnabled || !file_exists($cacheFile) ||
            filemtime($templatePath) > filemtime($cacheFile)) {
            $content = file_get_contents($templatePath);
            $compiled = $this->compileString($content, $templatePath);
            file_put_contents($cacheFile, $compiled);
        }

        return $cacheFile;
    }

    private function compileString(string $content, string $templatePath): string
    {
        $content = $this->addLineNumbers($content, $templatePath);

        foreach ($this->compilers as $compiler) {
            $content = $this->{"compile{$compiler}"}($content);
        }

        foreach ($this->customDirectives as $name => $handler) {
            $content = preg_replace_callback(
                "/@{$name}\s*(\( ( (?>[^()]+) | (?1) )* \))?/x",
                fn ($matches) => $handler($matches),
                $content
            );
        }

        return $content;
    }

    private function addLineNumbers(string $content, string $templatePath): string
    {
        return "<?php /* Source: {$templatePath} */ ?>\n".
               preg_replace('/\n/', "\n<?php /* Line: \$__line__ */ ?>\n", $content);
    }

    private function compileComments(string $content): string
    {
        return preg_replace('/{{--(.*?)--}}/s', '', $content);
    }

    private function compileDirectives(string $content): string
    {
        $patterns = [
            '/@if\s*\((.*)\)/' => '<?php if($1): ?>',
            '/@elseif\s*\((.*)\)/' => '<?php elseif($1): ?>',
            '/@else/' => '<?php else: ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@foreach\s*\((.*)\)/' => '<?php foreach($1): ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@for\s*\((.*)\)/' => '<?php for($1): ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@while\s*\((.*)\)/' => '<?php while($1): ?>',
            '/@endwhile/' => '<?php endwhile; ?>',
            '/@extends\([\'"](.*?)[\'"]\)/' => '<?php $this->extend("$1"); ?>',
            '/@section\([\'"](.*?)[\'"]\)/' => '<?php $this->startSection("$1"); ?>',
            '/@appendSection\([\'"](.*?)[\'"]\)/' => '<?php $this->startSection("$1", "append"); ?>',
            '/@prependSection\([\'"](.*?)[\'"]\)/' => '<?php $this->startSection("$1", "prepend"); ?>',
            '/@endsection/' => '<?php $this->endSection(); ?>',
            '/@yield\([\'"](.*?)[\'"]\)/' => '<?= $this->yieldSection("$1") ?>',
            '/@include\([\'"](.*?)[\'"]\)/' => '<?php include $this->compile("{$this->viewsPath}/$1.ace.php"); ?>',
            '/@component\([\'"](.*?)[\'"]\)/' => '<?php $this->startComponent("$1"); ?>',
            '/@endcomponent/' => '<?php $this->endComponent(); ?>',
            '/@slot\([\'"](.*?)[\'"]\)/' => '<?php $this->slot("$1"); ?>',
            '/@endslot/' => '<?php $this->endSlot(); ?>',
            '/@lang\([\'"](.*?)[\'"]\)/' => '<?= $this->translate("$1") ?>',
            '/@csrf/' => '<?= $this->generateCsrfField() ?>',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $content);
    }

    private function compileUnescaped(string $content): string
    {
        return preg_replace([
            '/\{\{\s*(.+?)\s*\}\}/',
            '/\{!!\s*(.+?)\s*!!\}/',
        ], [
            '<?= htmlspecialchars($1, ENT_QUOTES) ?>',
            '<?= $1 ?>',
        ], $content);
    }

    private function compilePhp(string $content): string
    {
        return preg_replace('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', '<?php $1$2$3 ?>', $content);
    }

    public function extend(string $view): void
    {
        $this->parentView = $view;
    }

    public function startSection(string $name, string $mode = 'replace'): void
    {
        $this->sectionStack[] = ['name' => $name, 'mode' => $mode];
        ob_start();
    }

    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new AceException("No active section to end.");
        }

        $section = array_pop($this->sectionStack);
        $content = ob_get_clean();

        if ($section['mode'] === 'append') {
            $this->sections[$section['name']] = ($this->sections[$section['name']] ?? '').$content;
        } elseif ($section['mode'] === 'prepend') {
            $this->sections[$section['name']] = $content.($this->sections[$section['name']] ?? '');
        } else {
            $this->sections[$section['name']] = $content;
        }
    }

    public function yieldSection(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    public function startComponent(string $name): void
    {
        array_push($this->componentStack, $name);
        $this->slots[$name] = ['__default__' => ''];
        ob_start();
    }

    public function endComponent(): void
    {
        $name = array_pop($this->componentStack);
        $content = ob_get_clean();
        $this->slots[$name]['__default__'] = $content;
        echo $this->render($name, ['slots' => $this->slots[$name]]);
        unset($this->slots[$name]);
    }

    public function slot(string $name): void
    {
        array_push($this->componentStack, $name);
        ob_start();
    }

    public function endSlot(): void
    {
        $slotName = array_pop($this->componentStack);
        $componentName = end($this->componentStack);
        $this->slots[$componentName][$slotName] = ob_get_clean();
    }

    private function translate(string $key): string
    {
        return $this->translator ? htmlspecialchars(($this->translator)($key), ENT_QUOTES) : $key;
    }

    private function generateCsrfField(): string
    {
        if (!$this->csrfTokenGenerator) {
            throw new AceException("CSRF token generator not configured.");
        }

        $token = ($this->csrfTokenGenerator)();
        return '<input type="hidden" name="_token" value="'.htmlspecialchars($token, ENT_QUOTES).'">';
    }

    private function formatError(Throwable $e, string $templatePath): string
    {
        if (!$this->debug) {
            return "Rendering error occurred.";
        }

        $lines = file($templatePath);
        $line = $e->getLine() - 2; // Account for line number comments
        $context = '';

        for ($i = max(0, $line - 3); $i < min(count($lines), $line + 3); $i++) {
            $context .= ($i + 1).': '.$lines[$i];
        }

        return sprintf(
            "Error in %s:%d\n%s\n\nTemplate Context:\n%s",
            $templatePath,
            $line,
            $e->getMessage(),
            $context
        );
    }
}
