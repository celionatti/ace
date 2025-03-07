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
        // Ask for style preference if not provided
        if (!isset($options['color']) || empty($options['color'])) {
            $options['color'] = TermUI::select("Choose a color for your greeting", [
                'green' => 'Green (default)',
                'blue' => 'Blue',
                'red' => 'Red',
                'yellow' => 'Yellow'
            ]);

            if ($options['color'] === null) {
                $options['color'] = 'green';
            }
        }

        // Map color names to TermUI constants
        $colorMap = [
            'green' => TermUI::GREEN,
            'blue' => TermUI::BLUE,
            'red' => TermUI::RED,
            'yellow' => TermUI::YELLOW
        ];

        $color = isset($colorMap[$options['color']]) ? $colorMap[$options['color']] : TermUI::GREEN;

        $name = $arguments['name'];
        $message = "Hello, {$name}!";

        if ($options['uppercase']) {
            $message = strtoupper($message);
        }

        $times = (int)$options['times'];
        $output = "";
        for ($i = 0; $i < $times; $i++) {
            $output .= $message . PHP_EOL;
        }

        TermUI::box("GREETING", $output, $color);

        return 0;
    }
}