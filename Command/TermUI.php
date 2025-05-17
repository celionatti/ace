<?php

declare(strict_types=1);

namespace Ace\Command;

class TermUI
{
    // ANSI color codes
    public const string RESET = "\033[0m";
    public const string BOLD = "\033[1m";
    public const string DIM = "\033[2m";
    public const string BLACK = "\033[30m";
    public const string RED = "\033[31m";
    public const string GREEN = "\033[32m";
    public const string YELLOW = "\033[33m";
    public const string BLUE = "\033[34m";
    public const string MAGENTA = "\033[35m";
    public const string CYAN = "\033[36m";
    public const string WHITE = "\033[37m";
    public const string BG_BLACK = "\033[40m";
    public const string BG_RED = "\033[41m";
    public const string BG_GREEN = "\033[42m";
    public const string BG_YELLOW = "\033[43m";
    public const string BG_BLUE = "\033[44m";
    public const string BG_MAGENTA = "\033[45m";
    public const string BG_CYAN = "\033[46m";
    public const string BG_WHITE = "\033[47m";

    /**
     * Draw a box with a title and content
     */
    public static function box($title, $content, $color = self::GREEN)
    {
        $lines = explode(PHP_EOL, $content);
        $width = 0;

        // Find the maximum line length
        foreach ($lines as $line) {
            $width = max($width, mb_strlen(strip_tags($line)));
        }

        // Add padding for the title
        $width = max($width, mb_strlen($title) + 4);

        // Add some extra padding
        $width += 4;

        // Top border with title
        echo $color . self::BOLD . "┌─" . $title . " " . str_repeat("─", $width - mb_strlen($title) - 3) . "┐" . self::RESET . PHP_EOL;

        // Content
        foreach ($lines as $line) {
            echo $color . "│ " . self::RESET . str_pad($line, $width) . $color . " │" . self::RESET . PHP_EOL;
        }

        // Bottom border
        echo $color . self::BOLD . "└" . str_repeat("─", $width + 2) . "┘" . self::RESET . PHP_EOL;
    }

    /**
     * Create a selection menu and return the selected option
     */
    public static function select($question, array $options, $color = self::GREEN)
    {
        echo $color . self::BOLD . "┌─" . $question . "────────────────────┐" . self::RESET . PHP_EOL;
        foreach ($options as $key => $option) {
            $padding = max(0, 20 - strlen($option)); // Ensure padding is never negative
            echo $color . "│ " . self::RESET . "[$key] $option" . str_repeat(" ", $padding) . $color . " │" . self::RESET . PHP_EOL;
        }
        echo $color . self::BOLD . "└───────────────────────────────┘" . self::RESET . PHP_EOL;
        echo $color . "Select an option: " . self::RESET;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        return isset($options[$line]) ? $line : null;
    }

    /**
     * Ask for input with a prompt
     */
    public static function prompt($question, $default = null, $color = self::GREEN)
    {
        $defaultText = $default !== null ? " [$default]" : "";
        echo $color . "$question$defaultText: " . self::RESET;

        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        return $line !== "" ? $line : $default;
    }

    /**
     * Output a success message
     */
    public static function success($message)
    {
        self::box("SUCCESS", $message, self::GREEN);
    }

    /**
     * Output an error message
     */
    public static function error($message)
    {
        self::box("ERROR", $message, self::RED);
    }

    /**
     * Output an info message
     */
    public static function info($message)
    {
        self::box("INFO", $message, self::BLUE);
    }
}