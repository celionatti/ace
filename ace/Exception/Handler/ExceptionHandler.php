<?php

declare(strict_types=1);

/**
 * ==============================================
 * ================         =====================
 * ExceptionHandler Class.
 * ================         =====================
 * ==============================================
 */

namespace Ace\ace\Exception\Handler;

use Throwable;
use Ace\ace\Http\Request;
use Ace\ace\Exception\AceException;

class ExceptionHandler
{
    private string $environment;
    private array $errorTypes = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        E_ALL => 'All Errors'
    ];

    public function __construct(string $environment = null)
    {
        $this->environment = $environment ?? ($_ENV['APP_ENV'] ?? 'production');
        $this->configureErrorHandling();
    }

    private function configureErrorHandling(): void
    {
        if ($this->environment === 'development') {
            error_reporting(-1);
            ini_set('display_errors', '1');
        } else {
            error_reporting(-1);
            ini_set('display_errors', '0');
        }

        ini_set('log_errors', '1');
        ini_set('error_log', defined('BASE_PATH') ? BASE_PATH . '/storage/logs/php_errors.log' : __DIR__ . '/../../storage/logs/php_errors.log');

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        $exception = new \ErrorException($message, 0, $level, $file, $line);
        $this->handleException($exception);
        exit(1);
        // return true;
    }

    public function handleException(Throwable $exception): void
    {
        $this->logException($exception);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if ($this->environment === 'development') {
            $this->renderDevelopmentError($exception);
        } else {
            $this->renderProductionError($exception);
        }
        exit(1);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private function logException(Throwable $exception): void
    {
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        error_log($message);
    }

    private function renderDevelopmentError(Throwable $exception): void
    {
        http_response_code(500);
        $exceptionType = get_class($exception);
        $title = $this->getErrorTitle($exception);
        $errorCode = $this->getErrorCode($exception);
        $frames = $this->getStackFrames($exception);
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $snippet = $this->getCodeSnippet($file, $line);
        $requestData = $this->getRequestData();
        $env = $this->getEnvironmentInfo();

        // Output the error page
        $cssStyles = $this->getCssStyles();
        $jsScripts = $this->getJsScripts();

        include __DIR__ . '/../errors/error-template.php';
        exit;
    }

    private function renderProductionError(Throwable $exception): void
    {
        http_response_code(500);
        $errorId = hash('sha256', microtime(true) . rand(10000, 99999));
        $this->logException($exception);

        // Include the production error view
        include defined('BASE_PATH') ? BASE_PATH . '/views/errors/500.html' : __DIR__ . '/../../views/errors/500.html';
        exit;
    }

    private function getErrorTitle(Throwable $exception): string
    {
        if ($exception instanceof \ErrorException) {
            return $this->errorTypes[$exception->getSeverity()] ?? 'Unknown Error';
        }

        return (new \ReflectionClass($exception))->getShortName();
    }

    private function getErrorCode(Throwable $exception): string
    {
        if ($exception instanceof AceException) {
            return $exception->getErrorCode();
        }
        return 'E' . $exception->getCode();
    }

    private function getStackFrames(Throwable $exception): array
    {
        $frames = [];
        $trace = $exception->getTrace();

        foreach ($trace as $index => $frame) {
            if (!isset($frame['file']) || !isset($frame['line'])) {
                continue;
            }

            $frames[] = [
                'index' => $index,
                'file' => $frame['file'],
                'line' => $frame['line'],
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
                'args' => $this->formatArgs($frame['args'] ?? []),
                'snippet' => $this->getCodeSnippet($frame['file'], $frame['line'])
            ];
        }

        return $frames;
    }

    private function formatArgs(array $args): array
    {
        return array_map(function ($arg) {
            if (is_object($arg)) {
                return get_class($arg);
            } elseif (is_array($arg)) {
                return 'Array(' . count($arg) . ')';
            } elseif (is_string($arg)) {
                return "'" . (strlen($arg) > 20 ? substr($arg, 0, 20) . '...' : $arg) . "'";
            } elseif (is_bool($arg)) {
                return $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                return 'null';
            }
            return (string) $arg;
        }, $args);
    }

    private function getCodeSnippet(string $file, int $line, int $padding = 8): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $lines = file($file);
        $start = max(0, $line - $padding - 1);
        $end = min(count($lines), $line + $padding);

        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = rtrim($lines[$i]);
        }

        return $snippet;
    }

    private function getRequestData(): array
    {
        return [
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'server' => $_SERVER,
            'get' => $_GET,
            'post' => $_POST,
            'cookies' => $_COOKIE,
            'session' => $_SESSION ?? [],
            'headers' => $this->getRequestHeaders()
        ];
    }

    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }
        return $headers;
    }

    private function getEnvironmentInfo(): array
    {
        return [
            'php_version' => phpversion(),
            'os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'framework_version' => defined('ACE_VERSION') ? ACE_VERSION : 'unknown',
            'environment' => $this->environment
        ];
    }

    private function getCssStyles(): string
    {
        return <<<'CSS'
        :root {
            --bg-color: rgba(250, 250, 252, 0.9);
            --card-bg: rgba(255, 255, 255, 0.85);
            --text-color: #333;
            --accent-color: #5469d4;
            --secondary-color: rgba(240, 240, 245, 0.85);
            --border-color: rgba(200, 200, 220, 0.5);
            --highlight-color: #ffcc00;
            --success-color: #4caf50;
            --info-color: #2196f3;
            --warning-color: #ff9800;
            --error-color: #f44336;
            --heading-color: #3c3c45;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --active-tab: rgba(84, 105, 212, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text-color);
            line-height: 1.6;
            font-size: 16px;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .error-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 20px;
        }

        .error-header {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .error-header > div:first-child {
            flex-grow: 1; /* Allows this div to take up remaining space */
            margin-right: 15px; /* Adds spacing between the two divs */
        }

        .env-item {
            flex-shrink: 0; /* Prevents it from growing, keeps it small */
            max-width: 150px; /* Adjust as needed */
            text-align: right;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--heading-color);
        }

        .error-code {
            font-size: 12px;
            background-color: var(--secondary-color);
            padding: 5px 10px;
            border-radius: 4px;
            margin-left: 10px;
        }

        .error-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--accent-color);
            font-weight: 600;
        }

        .error-location {
            background-color: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .error-location-file {
            font-family: monospace;
            font-size: 14px;
        }

        .error-tabs {
            display: flex;
            margin-bottom: 15px;
            background-color: var(--card-bg);
            border-radius: 10px 10px 0 0;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .error-tab {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }

        .error-tab:hover {
            background-color: rgba(84, 105, 212, 0.05);
        }

        .error-tab.active {
            border-bottom: 2px solid var(--accent-color);
            font-weight: 600;
            background-color: var(--active-tab);
        }

        .error-content {
            display: none;
            background-color: var(--card-bg);
            border-radius: 0 0 10px 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .error-content.active {
            display: block;
        }

        .code-snippet {
            background-color: var(--secondary-color);
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .code-snippet pre {
            padding: 15px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
        }

        .line-number {
            display: inline-block;
            width: 40px;
            color: #888;
            user-select: none;
        }

        .highlight-line {
            background-color: rgba(255, 107, 107, 0.2);
            display: block;
            width: 100%;
        }

        .stack-trace {
            margin-bottom: 20px;
        }

        .stack-frame {
            background-color: var(--secondary-color);
            border-radius: 5px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .stack-frame-header {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        .stack-frame-header:hover {
            background-color: rgba(84, 105, 212, 0.05);
        }

        .stack-frame-content {
            display: none;
            padding: 15px;
        }

        .stack-frame-content.active {
            display: block;
        }

        .stack-frame-function {
            font-family: monospace;
            font-weight: 600;
        }

        .stack-frame-location {
            font-size: 12px;
            color: #666;
        }

        .request-data {
            background-color: var(--secondary-color);
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .request-data-header {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        .request-data-header:hover {
            background-color: rgba(84, 105, 212, 0.05);
        }

        .request-data-content {
            display: none;
            padding: 15px;
        }

        .request-data-content.active {
            display: block;
        }

        .request-data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .request-data-table th, .request-data-table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .request-data-table th {
            font-weight: 600;
            color: var(--heading-color);
        }

        .env-info {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .env-item {
            background-color: var(--secondary-color);
            padding: 10px;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }

        .env-item-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .env-item-value {
            font-weight: 600;
            color: var(--heading-color);
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-error {
            background-color: var(--error-color);
            color: white;
        }

        .badge-warning {
            background-color: var(--warning-color);
            color: black;
        }

        .badge-info {
            background-color: var(--info-color);
            color: white;
        }

        .copy-button {
            background-color: rgba(255, 255, 255, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--accent-color);
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .copy-button:hover {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: var(--accent-color);
        }

        .solutions {
            background-color: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: var(--box-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .solutions-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--heading-color);
        }

        .solution-item {
            padding: 10px;
            border-left: 2px solid var(--info-color);
            margin-bottom: 10px;
            background-color: rgba(33, 150, 243, 0.05);
            border-radius: 0 5px 5px 0;
        }

        @media (max-width: 768px) {
            .error-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .error-location {
                flex-direction: column;
                align-items: flex-start;
            }

            .env-info {
                flex-direction: column;
            }

            .copy-button {
                margin-top: 10px;
            }
        }
        CSS;
    }

    private function getJsScripts(): string
    {
        return <<<'JS'
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.error-tab');
            const contents = document.querySelectorAll('.error-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    tab.classList.add('active');
                    const contentId = tab.getAttribute('data-target');
                    document.getElementById(contentId).classList.add('active');
                });
            });

            // Stack frame toggling
            const stackFrameHeaders = document.querySelectorAll('.stack-frame-header');

            stackFrameHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    content.classList.toggle('active');
                });
            });

            // Request data toggling
            const requestDataHeaders = document.querySelectorAll('.request-data-header');

            requestDataHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const content = header.nextElementSibling;
                    content.classList.toggle('active');
                });
            });

            // Copy functionality
            const copyButtons = document.querySelectorAll('.copy-button');

            copyButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const textToCopy = button.getAttribute('data-copy');
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        const originalText = button.textContent;
                        button.textContent = 'Copied!';
                        setTimeout(() => {
                            button.textContent = originalText;
                        }, 2000);
                    });
                });
            });
        });
        JS;
    }

    /**
     * Get suggested solutions based on the exception type and message
     *
     * @param Throwable $exception
     * @return array
     */
    private function getSuggestedSolutions(Throwable $exception): array
    {
        $solutions = [];
        $message = $exception->getMessage();
        $exceptionClass = get_class($exception);

        // Check specific exception types
        if ($exception instanceof \PDOException) {
            $solutions[] = "Check your database connection settings in the configuration file.";
            $solutions[] = "Make sure the database server is running and accessible.";

            if (stripos($message, 'access denied') !== false) {
                $solutions[] = "Verify that the database username and password are correct.";
            }

            if (stripos($message, 'unknown database') !== false) {
                $solutions[] = "Ensure that the specified database exists.";
            }
        }
        elseif ($exception instanceof \ErrorException) {
            // Handle different error types
            switch ($exception->getSeverity()) {
                case E_NOTICE:
                case E_USER_NOTICE:
                    $solutions[] = "This is a notice that does not stop execution but indicates potential issues.";
                    $solutions[] = "Consider using null coalescing operators (??) or isset() checks to handle undefined variables.";
                    break;

                case E_WARNING:
                case E_USER_WARNING:
                    if (stripos($message, 'undefined variable') !== false) {
                        $solutions[] = "Initialize the variable before use or add checks with isset() or empty().";
                    } elseif (stripos($message, 'file_get_contents') !== false) {
                        $solutions[] = "Check if the file exists and is readable before attempting to read it.";
                        $solutions[] = "Use file_exists() and is_readable() to validate the file path.";
                    }
                    break;

                case E_PARSE:
                    $solutions[] = "Fix the syntax error in your PHP code.";
                    $solutions[] = "Look for missing semicolons, brackets, or parentheses.";
                    break;

                case E_ERROR:
                case E_USER_ERROR:
                    if (stripos($message, 'memory') !== false) {
                        $solutions[] = "Increase the memory_limit in php.ini or via ini_set().";
                        $solutions[] = "Check for memory leaks or inefficient code.";
                    } elseif (stripos($message, 'maximum execution time') !== false) {
                        $solutions[] = "Increase the max_execution_time in php.ini or via ini_set().";
                        $solutions[] = "Optimize your code to execute more efficiently.";
                    } elseif (stripos($message, 'require') !== false || stripos($message, 'include') !== false) {
                        $solutions[] = "Verify that the required/included file exists at the specified path.";
                        $solutions[] = "Check file permissions to ensure PHP can read the file.";
                    }
                    break;
            }
        }
        elseif ($exception instanceof \RuntimeException) {
            $solutions[] = "This exception was thrown during program execution. Check the logic in your application.";

            if (stripos($message, 'permission') !== false) {
                $solutions[] = "Check file/directory permissions.";
                $solutions[] = "Ensure the web server has proper access to required resources.";
            }
        }
        elseif ($exception instanceof \InvalidArgumentException) {
            $solutions[] = "The arguments passed to a method or function are invalid.";
            $solutions[] = "Check the function call and ensure all arguments meet the expected format and constraints.";
        }
        elseif ($exception instanceof AceException) {
            // Framework specific exceptions
            if (method_exists($exception, 'getSolutions')) {
                $solutions = array_merge($solutions, $exception->getSolutions());
            }
        }

        // Check for common patterns in the error message
        if (stripos($message, 'class not found') !== false) {
            $solutions[] = "Make sure the class is properly imported or included.";
            $solutions[] = "Verify that namespaces are correctly specified.";
            $solutions[] = "Check autoloading configuration.";
        } elseif (stripos($message, 'call to undefined function') !== false) {
            $solutions[] = "Ensure that all required extensions are installed and enabled.";
            $solutions[] = "Check function name for typos.";
        } elseif (stripos($message, 'call to undefined method') !== false) {
            $solutions[] = "Verify that the method exists in the class you're calling it on.";
            $solutions[] = "Check for typos in the method name.";
        } elseif (stripos($message, 'undefined property') !== false) {
            $solutions[] = "Check that the property is defined and accessible from the current scope.";
            $solutions[] = "Initialize class properties in the constructor or as default values.";
        }

        return $solutions;
    }
}

// Helper function to get error handler instance
function aceErrorHandler(): ExceptionHandler
{
    static $handler = null;

    if ($handler === null) {
        $handler = new ExceptionHandler($_ENV['APP_ENV'] ?? 'production');
    }

    return $handler;
}

// Initialize the error handler
aceErrorHandler();