<?php

namespace Ace\Pagination;

use Exception;

class Paginator
{
    protected array $meta;
    protected string $baseUrl = '';
    protected string $queryParam = 'page';
    protected int $maxLinks = 5;
    protected array $templates = [
        'default' => '<nav class="pagination">{prev} {links} {next}</nav>',
        'bootstrap' => '
            <nav aria-label="Page navigation">
                <ul class="pagination">{prev} {links} {next}</ul>
            </nav>
        ',
        'tailwind' => '
            <nav class="flex items-center justify-between my-4">
                <div class="flex-1 flex justify-between sm:hidden">
                    {prev}
                    {next}
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <span class="relative z-0 inline-flex shadow-sm">
                            {prev} {links} {next}
                        </span>
                    </div>
                </div>
            </nav>
        '
    ];
    protected array $elementTemplates = [
        'link' => '<a href="{url}" class="pagination-link">{page}</a>',
        'active' => '<span class="pagination-active">{page}</span>',
        'disabled' => '<span class="pagination-disabled">{page}</span>',
        'ellipsis' => '<span class="pagination-ellipsis">...</span>',
        'bootstrap_link' => '
            <li class="page-item">
                <a class="page-link" href="{url}">{page}</a>
            </li>
        ',
        'bootstrap_active' => '
            <li class="page-item active">
                <span class="page-link">{page}</span>
            </li>
        ',
        'bootstrap_disabled' => '
            <li class="page-item disabled">
                <span class="page-link">{page}</span>
            </li>
        ',
        'bootstrap_ellipsis' => '
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
        ',
        'tailwind_link' => '
            <a href="{url}" class="ml-3 relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                {page}
            </a>
        ',
        'tailwind_active' => '
            <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-indigo-500 text-sm font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                {page}
            </span>
        ',
        'tailwind_disabled' => '
            <span class="ml-3 relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                {page}
            </span>
        ',
        'tailwind_ellipsis' => '
            <span class="ml-3 relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md text-gray-700 bg-white">
                ...
            </span>
        '
    ];

    public function __construct(array $meta)
    {
        $this->meta = $meta;
        $this->baseUrl = $this->getCurrentUrl();
    }

    public function render(string $style = 'default'): string
    {
        $method = 'render' . ucfirst($style);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->renderCustom($style);
    }

    public function renderBootstrap(): string
    {
        return $this->buildPagination('bootstrap');
    }

    public function renderTailwind(): string
    {
        return $this->buildPagination('tailwind');
    }

    public function renderCustom(string $templateName): string
    {
        if (!isset($this->templates[$templateName])) {
            throw new Exception("Template {$templateName} not found");
        }

        return $this->buildPagination($templateName);
    }

    public function setTemplate(string $name, string $template): self
    {
        $this->templates[$name] = $template;
        return $this;
    }

    public function setElementTemplate(string $type, string $template, string $style = 'default'): self
    {
        $this->elementTemplates["{$style}_{$type}"] = $template;
        return $this;
    }

    public function withQueryParam(string $param): self
    {
        $this->queryParam = $param;
        return $this;
    }

    public function withMaxLinks(int $max): self
    {
        $this->maxLinks = $max;
        return $this;
    }

    protected function buildPagination(string $style): string
    {
        $template = $this->templates[$style] ?? $this->templates['default'];
        $elements = [
            '{prev}' => $this->buildPreviousButton($style),
            '{next}' => $this->buildNextButton($style),
            '{links}' => $this->buildPageLinks($style)
        ];

        return str_replace(array_keys($elements), array_values($elements), $template);
    }

    protected function buildPageLinks(string $style): string
    {
        $links = [];
        $currentPage = $this->meta['current_page'];
        $lastPage = $this->meta['last_page'];

        $start = max(1, $currentPage - floor($this->maxLinks / 2));
        $end = min($lastPage, $start + $this->maxLinks - 1);

        if ($end - $start < $this->maxLinks) {
            $start = max(1, $end - $this->maxLinks + 1);
        }

        // Add first page with ellipsis if needed
        if ($start > 1) {
            $links[] = $this->buildPageElement(1, $style);
            if ($start > 2) {
                $links[] = $this->buildEllipsis($style);
            }
        }

        // Add page numbers
        for ($page = $start; $page <= $end; $page++) {
            $links[] = $this->buildPageElement($page, $style);
        }

        // Add last page with ellipsis if needed
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $links[] = $this->buildEllipsis($style);
            }
            $links[] = $this->buildPageElement($lastPage, $style);
        }

        return implode(' ', $links);
    }

    protected function buildPageElement(int $page, string $style): string
    {
        if ($page === $this->meta['current_page']) {
            return $this->buildActivePage($page, $style);
        }

        return $this->buildPageLink($page, $style);
    }

    protected function buildPageLink(int $page, string $style): string
    {
        $template = $this->elementTemplates["{$style}_link"] ?? $this->elementTemplates['link'];
        return str_replace(
            ['{url}', '{page}'],
            [$this->buildUrl($page), $page],
            $template
        );
    }

    protected function buildActivePage(int $page, string $style): string
    {
        $template = $this->elementTemplates["{$style}_active"] ?? $this->elementTemplates['active'];
        return str_replace('{page}', $page, $template);
    }

    protected function buildEllipsis(string $style): string
    {
        $template = $this->elementTemplates["{$style}_ellipsis"] ?? $this->elementTemplates['ellipsis'];
        return $template;
    }

    protected function buildPreviousButton(string $style): string
    {
        $currentPage = $this->meta['current_page'];
        $disabled = $currentPage <= 1;

        if ($disabled) {
            $template = $this->elementTemplates["{$style}_disabled"] ?? $this->elementTemplates['disabled'];
            return str_replace('{page}', 'Previous', $template);
        }

        $template = $this->elementTemplates["{$style}_link"] ?? $this->elementTemplates['link'];
        return str_replace(
            ['{url}', '{page}'],
            [$this->buildUrl($currentPage - 1), 'Previous'],
            $template
        );
    }

    protected function buildNextButton(string $style): string
    {
        $currentPage = $this->meta['current_page'];
        $lastPage = $this->meta['last_page'];
        $disabled = $currentPage >= $lastPage;

        if ($disabled) {
            $template = $this->elementTemplates["{$style}_disabled"] ?? $this->elementTemplates['disabled'];
            return str_replace('{page}', 'Next', $template);
        }

        $template = $this->elementTemplates["{$style}_link"] ?? $this->elementTemplates['link'];
        return str_replace(
            ['{url}', '{page}'],
            [$this->buildUrl($currentPage + 1), 'Next'],
            $template
        );
    }

    protected function buildUrl(int $page): string
    {
        $query = $_GET;
        $query[$this->queryParam] = $page;

        $parsed = parse_url($this->baseUrl);
        $path = $parsed['path'] ?? '/';
        $queryString = http_build_query($query);

        return "{$path}?{$queryString}";
    }

    protected function getCurrentUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    }
}