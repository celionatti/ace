<?php

declare(strict_types=1);

namespace Ace\ace\Pagination;

class Pagination
{
    /**
     * The total number of items
     */
    private int $total;

    /**
     * Number of items per page
     */
    private int $perPage;

    /**
     * Current page number
     */
    private int $currentPage;

    /**
     * Last page number
     */
    private int $lastPage;

    /**
     * Starting item number
     */
    private int $from;

    /**
     * Ending item number
     */
    private int $to;

    /**
     * Number of page links to display
     */
    private int $onEachSide = 3;

    /**
     * Theme configuration
     */
    private string $theme = 'default';

    /**
     * Available themes
     */
    private array $themes = [
        'default', 'bootstrap', 'tailwind', 'semantic-ui', 'bulma', 'material'
    ];

    /**
     * Layout style
     */
    private string $layout = 'default';

    /**
     * Available layouts
     */
    private array $layouts = [
        'default', 'simple', 'load-more', 'infinite-scroll'
    ];

    /**
     * Constructor
     *
     * @param array $paginationData Pagination data from the backend
     * @param array $options Additional options for rendering
     */
    public function __construct(array $paginationData, array $options = [])
    {
        // Set pagination data
        $this->total = $paginationData['meta']['total'] ?? 0;
        $this->perPage = $paginationData['meta']['per_page'] ?? 15;
        $this->currentPage = $paginationData['meta']['current_page'] ?? 1;
        $this->lastPage = $paginationData['meta']['last_page'] ?? 1;
        $this->from = $paginationData['meta']['from'] ?? 0;
        $this->to = $paginationData['meta']['to'] ?? 0;

        // Set options
        $this->onEachSide = $options['on_each_side'] ?? 3;
        $this->theme = in_array($options['theme'] ?? 'default', $this->themes) ? $options['theme'] : 'default';
        $this->layout = in_array($options['layout'] ?? 'default', $this->layouts) ? $options['layout'] : 'default';
    }

    /**
     * Generate the pagination HTML
     *
     * @param string $baseUrl The base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML code for pagination
     */
    public function render(string $baseUrl, array $queryParams = []): string
    {
        if ($this->total <= 0) {
            return '';
        }

        // Call the appropriate rendering method based on layout
        switch ($this->layout) {
            case 'simple':
                return $this->renderSimple($baseUrl, $queryParams);
            case 'load-more':
                return $this->renderLoadMore($baseUrl, $queryParams);
            case 'infinite-scroll':
                return $this->renderInfiniteScroll($baseUrl, $queryParams);
            default:
                return $this->renderDefault($baseUrl, $queryParams);
        }
    }

    /**
     * Render the default pagination layout
     *
     * @param string $baseUrl The base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML code for pagination
     */
    private function renderDefault(string $baseUrl, array $queryParams = []): string
    {
        $elements = $this->getElements();
        $html = $this->getWrapperStart();

        // "Previous" link
        $html .= $this->getPreviousButton($baseUrl, $queryParams);

        // Page Links
        foreach ($elements as $element) {
            if (is_string($element)) {
                $html .= $this->getDots();
            } else {
                foreach ($element as $page => $url) {
                    $html .= $this->getPageLink($page, $baseUrl, $queryParams);
                }
            }
        }

        // "Next" link
        $html .= $this->getNextButton($baseUrl, $queryParams);

        $html .= $this->getWrapperEnd();
        return $html;
    }

    /**
     * Render the simple pagination layout (previous/next only)
     *
     * @param string $baseUrl The base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML code for pagination
     */
    private function renderSimple(string $baseUrl, array $queryParams = []): string
    {
        $html = $this->getWrapperStart('simple');

        // "Previous" link
        $html .= $this->getPreviousButton($baseUrl, $queryParams);

        // Current page info
        $html .= $this->getPageInfo();

        // "Next" link
        $html .= $this->getNextButton($baseUrl, $queryParams);

        $html .= $this->getWrapperEnd('simple');
        return $html;
    }

    /**
     * Render the load-more pagination layout
     *
     * @param string $baseUrl The base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML code for pagination
     */
    private function renderLoadMore(string $baseUrl, array $queryParams = []): string
    {
        if ($this->currentPage >= $this->lastPage) {
            return $this->getNoMoreItemsMessage();
        }

        $html = $this->getWrapperStart('load-more');
        $html .= $this->getLoadMoreButton($baseUrl, $queryParams);
        $html .= $this->getWrapperEnd('load-more');
        return $html;
    }

    /**
     * Render the infinite-scroll pagination layout
     *
     * @param string $baseUrl The base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML code for pagination
     */
    private function renderInfiniteScroll(string $baseUrl, array $queryParams = []): string
    {
        if ($this->currentPage >= $this->lastPage) {
            return '';
        }

        $nextPage = $this->currentPage + 1;
        $nextPageUrl = $this->getUrl($baseUrl, $nextPage, $queryParams);

        $html = '<div class="pagination-infinite-scroll">';
        $html .= '<div class="pagination-loading" data-next-page="' . htmlspecialchars($nextPageUrl) . '">';
        $html .= '<span class="pagination-loading-text">Loading more items...</span>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get pagination elements (dots and page links)
     *
     * @return array Array of elements (dots and page links)
     */
    private function getElements(): array
    {
        $window = $this->getWindow();
        $elements = [];

        // Generate the page links
        if ($this->lastPage <= ($this->onEachSide * 2) + 3) {
            // Show all pages
            $elements[] = $this->getPageRange(1, $this->lastPage);
        } else {
            // First page
            $elements[] = $this->getPageRange(1, 1);

            // First set of links
            if ($this->currentPage <= $this->onEachSide + 2) {
                $elements[] = $this->getPageRange(2, $this->onEachSide + 2);
            } elseif ($this->currentPage >= $this->lastPage - ($this->onEachSide + 1)) {
                $elements[] = '...';
            } else {
                $elements[] = '...';
            }

            // Middle set of links
            if ($this->currentPage > $this->onEachSide + 2 && $this->currentPage < $this->lastPage - ($this->onEachSide + 1)) {
                $elements[] = $this->getPageRange($this->currentPage - $this->onEachSide, $this->currentPage + $this->onEachSide);
            }

            // Last set of links
            if ($this->currentPage <= $this->onEachSide + 2) {
                $elements[] = '...';
            } elseif ($this->currentPage >= $this->lastPage - ($this->onEachSide + 1)) {
                $elements[] = $this->getPageRange($this->lastPage - ($this->onEachSide + 1), $this->lastPage - 1);
            } else {
                $elements[] = '...';
            }

            // Last page
            $elements[] = $this->getPageRange($this->lastPage, $this->lastPage);
        }

        return $elements;
    }

    /**
     * Get the current window of pages to display
     *
     * @return array
     */
    private function getWindow(): array
    {
        $window = [];
        $start = max(1, $this->currentPage - $this->onEachSide);
        $end = min($this->lastPage, $this->currentPage + $this->onEachSide);

        for ($i = $start; $i <= $end; $i++) {
            $window[] = $i;
        }

        return $window;
    }

    /**
     * Get a range of page numbers
     *
     * @param int $start Starting page
     * @param int $end Ending page
     * @return array
     */
    private function getPageRange(int $start, int $end): array
    {
        $range = [];
        for ($i = $start; $i <= $end; $i++) {
            $range[$i] = $i;
        }
        return $range;
    }

    /**
     * Generate the URL for a specific page
     *
     * @param string $baseUrl The base URL
     * @param int $page The page number
     * @param array $queryParams Additional query parameters
     * @return string The URL for the page
     */
    private function getUrl(string $baseUrl, int $page, array $queryParams = []): string
    {
        $queryParams['page'] = $page;
        return $baseUrl . '?' . http_build_query($queryParams);
    }

    /**
     * Get the wrapper start based on the theme
     *
     * @param string $layout The layout type (default, simple, load-more)
     * @return string HTML for the wrapper start
     */
    private function getWrapperStart(string $layout = 'default'): string
    {
        switch ($this->theme) {
            case 'bootstrap':
                return '<nav aria-label="Page navigation"><ul class="pagination">';
            case 'tailwind':
                return '<nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0">';
            case 'semantic-ui':
                return '<div class="ui pagination menu">';
            case 'bulma':
                return '<nav class="pagination" role="navigation" aria-label="pagination">';
            case 'material':
                return '<div class="mdc-pagination">';
            default:
                return '<div class="pagination">';
        }
    }

    /**
     * Get the wrapper end based on the theme
     *
     * @param string $layout The layout type (default, simple, load-more)
     * @return string HTML for the wrapper end
     */
    private function getWrapperEnd(string $layout = 'default'): string
    {
        switch ($this->theme) {
            case 'bootstrap':
                return '</ul></nav>';
            case 'tailwind':
                return '</nav>';
            case 'semantic-ui':
                return '</div>';
            case 'bulma':
                return '</nav>';
            case 'material':
                return '</div>';
            default:
                return '</div>';
        }
    }

    /**
     * Get the previous button HTML
     *
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string HTML for the previous button
     */
    private function getPreviousButton(string $baseUrl, array $queryParams = []): string
    {
        $previousPage = max(1, $this->currentPage - 1);
        $disabled = $this->currentPage <= 1;
        $url = $this->getUrl($baseUrl, $previousPage, $queryParams);

        switch ($this->theme) {
            case 'bootstrap':
                return '<li class="page-item' . ($disabled ? ' disabled' : '') . '">
                    <a class="page-link" href="' . ($disabled ? '#' : htmlspecialchars($url)) . '" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>';
            case 'tailwind':
                return '<div class="flex-1 flex justify-between sm:hidden">
                    <a href="' . ($disabled ? '#' : htmlspecialchars($url)) . '" class="' .
                    ($disabled ? 'opacity-50 cursor-not-allowed ' : '') .
                    'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>';
            case 'semantic-ui':
                return '<a class="' . ($disabled ? 'disabled ' : '') . 'item" href="' .
                    ($disabled ? '#' : htmlspecialchars($url)) . '">
                    <i class="chevron left icon"></i>
                </a>';
            case 'bulma':
                return '<a class="pagination-previous" ' . ($disabled ? 'disabled' : 'href="' . htmlspecialchars($url) . '"') . '>Previous</a>';
            case 'material':
                return '<button class="mdc-icon-button material-icons mdc-pagination__button' .
                    ($disabled ? ' mdc-pagination__button--disabled' : '') . '"' .
                    ($disabled ? ' disabled' : ' onclick="window.location=\'' . htmlspecialchars($url) . '\'"') . '>
                    <span class="mdc-icon-button__ripple"></span>keyboard_arrow_left
                </button>';
            default:
                return '<a class="pagination-prev' . ($disabled ? ' disabled' : '') . '" href="' .
                    ($disabled ? '#' : htmlspecialchars($url)) . '">Previous</a>';
        }
    }

    /**
     * Get the next button HTML
     *
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string HTML for the next button
     */
    private function getNextButton(string $baseUrl, array $queryParams = []): string
    {
        $nextPage = min($this->lastPage, $this->currentPage + 1);
        $disabled = $this->currentPage >= $this->lastPage;
        $url = $this->getUrl($baseUrl, $nextPage, $queryParams);

        switch ($this->theme) {
            case 'bootstrap':
                return '<li class="page-item' . ($disabled ? ' disabled' : '') . '">
                    <a class="page-link" href="' . ($disabled ? '#' : htmlspecialchars($url)) . '" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>';
            case 'tailwind':
                return '<div class="mt-3 flex justify-between sm:flex-1 sm:justify-end">
                    <a href="' . ($disabled ? '#' : htmlspecialchars($url)) . '" class="' .
                    ($disabled ? 'opacity-50 cursor-not-allowed ' : '') .
                    'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                </div>';
            case 'semantic-ui':
                return '<a class="' . ($disabled ? 'disabled ' : '') . 'item" href="' .
                    ($disabled ? '#' : htmlspecialchars($url)) . '">
                    <i class="chevron right icon"></i>
                </a>';
            case 'bulma':
                return '<a class="pagination-next" ' . ($disabled ? 'disabled' : 'href="' . htmlspecialchars($url) . '"') . '>Next page</a>';
            case 'material':
                return '<button class="mdc-icon-button material-icons mdc-pagination__button' .
                    ($disabled ? ' mdc-pagination__button--disabled' : '') . '"' .
                    ($disabled ? ' disabled' : ' onclick="window.location=\'' . htmlspecialchars($url) . '\'"') . '>
                    <span class="mdc-icon-button__ripple"></span>keyboard_arrow_right
                </button>';
            default:
                return '<a class="pagination-next' . ($disabled ? ' disabled' : '') . '" href="' .
                    ($disabled ? '#' : htmlspecialchars($url)) . '">Next</a>';
        }
    }

    /**
     * Get the HTML for page links
     *
     * @param int $page The page number
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string HTML for the page link
     */
    private function getPageLink(int $page, string $baseUrl, array $queryParams = []): string
    {
        $isCurrent = $page === $this->currentPage;
        $url = $this->getUrl($baseUrl, $page, $queryParams);

        switch ($this->theme) {
            case 'bootstrap':
                return '<li class="page-item' . ($isCurrent ? ' active' : '') . '">
                    <a class="page-link" href="' . htmlspecialchars($url) . '">' . $page . '</a>
                </li>';
            case 'tailwind':
                if ($isCurrent) {
                    return '<a href="#" aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-indigo-500 bg-indigo-50 text-sm font-medium text-indigo-600 z-10">' . $page . '</a>';
                }
                return '<a href="' . htmlspecialchars($url) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $page . '</a>';
            case 'semantic-ui':
                return '<a class="' . ($isCurrent ? 'active ' : '') . 'item" href="' .
                    ($isCurrent ? '#' : htmlspecialchars($url)) . '">' . $page . '</a>';
            case 'bulma':
                return '<li><a class="pagination-link' . ($isCurrent ? ' is-current' : '') . '"
                    aria-label="Page ' . $page . '"
                    aria-current="' . ($isCurrent ? 'page' : 'false') . '"
                    href="' . htmlspecialchars($url) . '">' . $page . '</a></li>';
            case 'material':
                return '<a class="mdc-pagination__item' . ($isCurrent ? ' mdc-pagination__item--active' : '') . '"
                    href="' . htmlspecialchars($url) . '">' . $page . '</a>';
            default:
                return '<a class="pagination-link' . ($isCurrent ? ' active' : '') . '"
                    href="' . htmlspecialchars($url) . '">' . $page . '</a>';
        }
    }

    /**
     * Get the HTML for ellipsis (dots)
     *
     * @return string HTML for ellipsis
     */
    private function getDots(): string
    {
        switch ($this->theme) {
            case 'bootstrap':
                return '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            case 'tailwind':
                return '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">&hellip;</span>';
            case 'semantic-ui':
                return '<div class="disabled item">&hellip;</div>';
            case 'bulma':
                return '<li><span class="pagination-ellipsis">&hellip;</span></li>';
            case 'material':
                return '<span class="mdc-pagination__dots">&hellip;</span>';
            default:
                return '<span class="pagination-ellipsis">&hellip;</span>';
        }
    }

    /**
     * Get the HTML for page information (used in simple pagination)
     *
     * @return string HTML for page information
     */
    private function getPageInfo(): string
    {
        $text = "Page {$this->currentPage} of {$this->lastPage}";

        switch ($this->theme) {
            case 'bootstrap':
                return '<li class="page-item"><span class="page-link">' . $text . '</span></li>';
            case 'tailwind':
                return '<div class="hidden md:flex-1 md:flex md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">' . $this->from . '</span> to <span class="font-medium">' . $this->to . '</span> of <span class="font-medium">' . $this->total . '</span> results
                        </p>
                    </div>
                </div>';
            case 'semantic-ui':
                return '<div class="item">' . $text . '</div>';
            case 'bulma':
                return '<ul class="pagination-list"><li>' . $text . '</li></ul>';
            case 'material':
                return '<span class="mdc-pagination__page-info">' . $text . '</span>';
            default:
                return '<span class="pagination-info">' . $text . '</span>';
        }
    }

    /**
     * Get the HTML for the Load More button
     *
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string HTML for the Load More button
     */
    private function getLoadMoreButton(string $baseUrl, array $queryParams = []): string
    {
        $nextPage = $this->currentPage + 1;
        $url = $this->getUrl($baseUrl, $nextPage, $queryParams);
        $remainingItems = $this->total - $this->to;

        switch ($this->theme) {
            case 'bootstrap':
                return '<button class="btn btn-primary load-more-btn" data-url="' . htmlspecialchars($url) . '">
                    Load More (' . $remainingItems . ' remaining)
                </button>';
            case 'tailwind':
                return '<button class="mt-3 w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 load-more-btn" data-url="' . htmlspecialchars($url) . '">
                    Load More (' . $remainingItems . ' remaining)
                </button>';
            case 'semantic-ui':
                return '<button class="fluid ui button load-more-btn" data-url="' . htmlspecialchars($url) . '">
                    Load More (' . $remainingItems . ' remaining)
                </button>';
            case 'bulma':
                return '<button class="button is-fullwidth is-primary load-more-btn" data-url="' . htmlspecialchars($url) . '">
                    Load More (' . $remainingItems . ' remaining)
                </button>';
            case 'material':
                return '<button class="mdc-button mdc-button--raised load-more-btn" data-url="' . htmlspecialchars($url) . '">
                    <span class="mdc-button__label">Load More (' . $remainingItems . ' remaining)</span>
                </button>';
            default:
                return '<button class="pagination-load-more" data-url="' . htmlspecialchars($url) . '">
                    Load More (' . $remainingItems . ' remaining)
                </button>';
        }
    }

    /**
     * Get the HTML for the "No more items" message
     *
     * @return string HTML for the "No more items" message
     */
    private function getNoMoreItemsMessage(): string
    {
        switch ($this->theme) {
            case 'bootstrap':
                return '<div class="alert alert-info text-center">No more items to load</div>';
            case 'tailwind':
                return '<div class="rounded-md bg-blue-50 p-4 mt-3">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">No more items to load</p>
                        </div>
                    </div>
                </div>';
            case 'semantic-ui':
                return '<div class="ui info message">No more items to load</div>';
            case 'bulma':
                return '<div class="notification is-info is-light">No more items to load</div>';
            case 'material':
                return '<div class="mdc-banner" role="status">
                    <div class="mdc-banner__content">
                        <div class="mdc-banner__text">No more items to load</div>
                    </div>
                </div>';
            default:
                return '<div class="pagination-no-more">No more items to load</div>';
        }
    }

    /**
     * Set the number of pages on each side of the current page
     *
     * @param int $count Number of links
     * @return $this
     */
    public function onEachSide(int $count): self
    {
        $this->onEachSide = max(0, $count);
        return $this;
    }

    /**
     * Set the theme for rendering
     *
     * @param string $theme Theme name
     * @return $this
     */
    public function theme(string $theme): self
    {
        if (in_array($theme, $this->themes)) {
            $this->theme = $theme;
        }
        return $this;
    }

    /**
     * Set the layout for rendering
     *
     * @param string $layout Layout name
     * @return $this
     */
    public function layout(string $layout): self
    {
        if (in_array($layout, $this->layouts)) {
            $this->layout = $layout;
        }
        return $this;
    }

    /**
     * Get pagination metadata
     *
     * @return array
     */
    public function getMeta(): array
    {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'from' => $this->from,
            'to' => $this->to
        ];
    }

    /**
     * Check if there are more pages to show
     *
     * @return bool
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /**
     * Get the URL for the next page
     *
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string|null
     */
    public function nextPageUrl(string $baseUrl, array $queryParams = []): ?string
    {
        if ($this->hasMorePages()) {
            return $this->getUrl($baseUrl, $this->currentPage + 1, $queryParams);
        }
        return null;
    }

    /**
     * Get the URL for the previous page
     *
     * @param string $baseUrl The base URL
     * @param array $queryParams Additional query parameters
     * @return string|null
     */
    public function previousPageUrl(string $baseUrl, array $queryParams = []): ?string
    {
        if ($this->currentPage > 1) {
            return $this->getUrl($baseUrl, $this->currentPage - 1, $queryParams);
        }
        return null;
    }
}