/**
 * Ace Pagination JavaScript - Handles interactive pagination features
 *
 * Use with the Ace\ace\Components\Pagination PHP class
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Load More functionality
    initLoadMorePagination();

    // Initialize Infinite Scroll functionality
    initInfiniteScrollPagination();
});

/**
 * Initialize Load More pagination buttons
 */
function initLoadMorePagination() {
    const loadMoreButtons = document.querySelectorAll('.load-more-btn');

    loadMoreButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = button.getAttribute('data-url');
            loadMoreContent(url, button);
        });
    });
}

/**
 * Initialize Infinite Scroll pagination
 */
function initInfiniteScrollPagination() {
    const infiniteScrollContainers = document.querySelectorAll('.pagination-infinite-scroll');

    if (infiniteScrollContainers.length > 0) {
        // Create IntersectionObserver to detect when loading indicator is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const loadingElement = entry.target;
                    const url = loadingElement.getAttribute('data-next-page');

                    if (url) {
                        // Prevent multiple loads of the same page
                        loadingElement.setAttribute('data-next-page', '');

                        // Load the next page content
                        loadInfiniteScrollContent(url, loadingElement);
                    }
                }
            });
        }, {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        });

        // Observe each loading indicator
        document.querySelectorAll('.pagination-loading').forEach(loader => {
            observer.observe(loader);
        });
    }
}

/**
 * Load more content via AJAX for Load More pagination
 *
 * @param {string} url URL to fetch content from
 * @param {HTMLElement} button The Load More button element
 */
function loadMoreContent(url, button) {
    // Show loading state
    const originalText = button.innerHTML;
    button.innerHTML = 'Loading...';
    button.disabled = true;

    // Make AJAX request
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        // Find the content container (should be before the pagination container)
        const paginationContainer = button.closest('.pagination-wrapper') || button.parentNode;
        const contentContainer = document.querySelector('[data-pagination-content]') || paginationContainer.previousElementSibling;

        // Create a temporary div to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // Extract the new content and pagination
        const newContent = tempDiv.querySelector('[data-pagination-content]') || tempDiv.firstElementChild;
        const newPagination = tempDiv.querySelector('.pagination') ||
                             tempDiv.querySelector('.pagination-wrapper') ||
                             tempDiv.querySelector('[data-pagination]');

        // Append new content
        if (contentContainer && newContent) {
            contentContainer.innerHTML += newContent.innerHTML;
        }

        // Replace pagination
        if (paginationContainer && newPagination) {
            paginationContainer.outerHTML = newPagination.outerHTML;

            // Re-initialize pagination for new elements
            initLoadMorePagination();
        } else {
            // No more pages
            button.outerHTML = '<div class="pagination-end">No more items to load</div>';
        }
    })
    .catch(error => {
        console.error('Error loading more content:', error);
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

/**
 * Load content via AJAX for Infinite Scroll pagination
 *
 * @param {string} url URL to fetch content from
 * @param {HTMLElement} loadingElement The loading indicator element
 */
function loadInfiniteScrollContent(url, loadingElement) {
    // Show loading state
    loadingElement.classList.add('loading');

    // Make AJAX request
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        // Find the content container
        const scrollContainer = loadingElement.closest('.pagination-infinite-scroll');
        const contentContainer = document.querySelector('[data-pagination-content]') || scrollContainer.previousElementSibling;

        // Create a temporary div to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // Extract the new content and pagination
        const newContent = tempDiv.querySelector('[data-pagination-content]') || tempDiv.firstElementChild;
        const newPagination = tempDiv.querySelector('.pagination-infinite-scroll');

        // Append new content
        if (contentContainer && newContent) {
            contentContainer.innerHTML += newContent.innerHTML;
        }

        // Update or remove the pagination container
        if (newPagination) {
            const newLoader = newPagination.querySelector('.pagination-loading');
            if (newLoader) {
                // Update next page URL
                const nextPageUrl = newLoader.getAttribute('data-next-page');
                loadingElement.setAttribute('data-next-page', nextPageUrl);
                loadingElement.classList.remove('loading');

                // Re-observe the loading element
                if (window.IntersectionObserver) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && nextPageUrl) {
                                loadingElement.setAttribute('data-next-page', '');
                                loadInfiniteScrollContent(nextPageUrl, loadingElement);
                            }
                        });
                    }, {
                        threshold: 0.1
                    });

                    observer.observe(loadingElement);
                }
            } else {
                // No more pages
                scrollContainer.remove();
            }
        } else {
            // No more pages
            scrollContainer.remove();
        }
    })
    .catch(error => {
        console.error('Error loading infinite scroll content:', error);
        loadingElement.classList.remove('loading');

        // Re-enable loading after a delay
        setTimeout(() => {
            const url = loadingElement.getAttribute('data-next-page');
            if (url) {
                loadingElement.setAttribute('data-next-page', url);
            }
        }, 3000);
    });
}

/**
 * Helper function to detect Ajax requests
 *
 * @return {boolean} True if the current request is via AJAX
 */
function isAjaxRequest() {
    return window.XMLHttpRequest &&
           window.location.pathname.indexOf('/ajax/') !== -1 ||
           new URLSearchParams(window.location.search).has('ajax');
}