<?php

declare(strict_types=1);

namespace Ace\ace\Command;

class TermUI
{
    // ANSI color codes
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
    const DIM = "\033[2m";
    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const BG_BLACK = "\033[40m";
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_MAGENTA = "\033[45m";
    const BG_CYAN = "\033[46m";
    const BG_WHITE = "\033[47m";

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