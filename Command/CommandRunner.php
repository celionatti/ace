<?php

declare(strict_types=1);

namespace Ace\Command;

use Ace\Command\Command;

class CommandRunner
{
    protected $name;
    protected $version;
    protected $commands = [];

    public function __construct($name = 'CLI Tool', $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * Register a command
     */
    public function register(Command $command)
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * Auto-discover commands in a directory
     */
    public function discover($path, $namespace)
    {
        if (!is_dir($path)) {
            return $this;
        }

        foreach (glob($path . '/*.php') as $file) {
            $className = $namespace . '\\' . basename($file, '.php');
            if (class_exists($className) && is_subclass_of($className, Command::class)) {
                $this->register(new $className());
            }
        }

        return $this;
    }

    /**
     * Show list of available commands
     */
    public function showAvailableCommands()
    {
        $content = $this->name . " v" . $this->version . PHP_EOL . PHP_EOL;
        $content .= "Available commands:" . PHP_EOL;

        foreach ($this->commands as $name => $command) {
            $content .= "  " . TermUI::GREEN . $name . TermUI::RESET . ": " . $command->getDescription() . PHP_EOL;
        }

        $content .= PHP_EOL;
        $content .= "Run '[command] --help' for more information about a command." . PHP_EOL;

        TermUI::box("HELP", $content, TermUI::BLUE);
    }

    /**
     * Let the user select a command interactively
     */
    public function selectCommand()
    {
        $options = [];
        foreach ($this->commands as $name => $command) {
            $options[$name] = $name . ": " . $command->getDescription();
        }

        $selected = TermUI::select("Select a command", $options);

        if ($selected === null) {
            TermUI::error("Invalid selection");
            return null;
        }

        return $selected;
    }

    /**
     * Run the CLI tool
     */
    public function run()
    {
        $args = $_SERVER['argv'];
        $script = array_shift($args);

        if (empty($args) || $args[0] === 'list' || $args[0] === '--help') {
            $this->showAvailableCommands();

            // If interactive and no command specified, prompt for command
            if (empty($args)) {
                $commandName = $this->selectCommand();
                if ($commandName === null) {
                    return 1;
                }

                return $this->commands[$commandName]->run([]);
            }

            return 0;
        }

        $commandName = array_shift($args);

        if (!isset($this->commands[$commandName])) {
            TermUI::error("Command not found: " . $commandName);
            TermUI::info("Run with --help to see available commands.");
            return 1;
        }

        return $this->commands[$commandName]->run($args);
    }
}