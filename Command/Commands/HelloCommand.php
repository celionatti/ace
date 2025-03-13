<?php

declare(strict_types=1);

namespace Ace\ace\Command\Commands;

use Ace\ace\Command\Command;
use Ace\ace\Command\TermUI;

class HelloCommand extends Command
{
    public function __construct()
    {
        $this->name = 'hello';
        $this->description = 'Say hello to someone';

        $this->addArgument('name', 'The name of the person to greet', false, 'World');
        $this->addOption('uppercase', 'u', 'Display greeting in uppercase', false);
        $this->addOption('times', 't', 'Number of times to repeat the greeting', true, 1);
        $this->addOption('color', 'c', 'Color of the greeting (green, blue, red, yellow)', true, 'green');
    }

    public function handle($arguments, $options)
    {
        // Get name argument
        $name = $arguments['name'];

        // Handle uppercase option
        $message = "Hello, {$name}!";
        if (isset($options['uppercase']) && $options['uppercase']) {
            $message = strtoupper($message);
        }

        // Handle times option
        $times = isset($options['times']) ? (int)$options['times'] : 1;

        // Handle color option
        $colorName = isset($options['color']) ? $options['color'] : 'green';
        $colorMap = [
            'green' => TermUI::GREEN,
            'blue' => TermUI::BLUE,
            'red' => TermUI::RED,
            'yellow' => TermUI::YELLOW
        ];
        $color = isset($colorMap[$colorName]) ? $colorMap[$colorName] : TermUI::GREEN;

        // Build output
        $output = "";
        for ($i = 0; $i < $times; $i++) {
            $output .= $message . PHP_EOL;
        }

        // Display output
        TermUI::box("GREETING", $output, $color);

        return 0;
    }
}