<?php

declare(strict_types=1);

namespace Ace\Command;

abstract class Command
{
    protected $name;
    protected $description;
    protected $arguments = [];
    protected $options = [];

    /**
     * Get the command name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the command description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Register an argument
     */
    protected function addArgument($name, $description = '', $isRequired = false, $default = null)
    {
        $this->arguments[$name] = [
            'description' => $description,
            'required' => $isRequired,
            'default' => $default
        ];
        return $this;
    }

    /**
     * Register an option
     */
    protected function addOption($name, $shortcut = null, $description = '', $requiresValue = false, $default = null)
    {
        $this->options[$name] = [
            'shortcut' => $shortcut,
            'description' => $description,
            'requires_value' => $requiresValue,
            'default' => $default
        ];
        return $this;
    }

    /**
     * Parse command line arguments - FIXED version
     */
    protected function parseInput($args)
    {
        $parsed = [
            'command' => $this->name,
            'arguments' => [],
            'options' => []
        ];

        // Set default values for arguments and options
        foreach ($this->arguments as $name => $argument) {
            $parsed['arguments'][$name] = $argument['default'];
        }

        foreach ($this->options as $name => $option) {
            $parsed['options'][$name] = $option['default'];
        }

        // Keep track of positional arguments
        $positionalArgs = [];
        $waitingForOptionValue = false;
        $currentOption = null;

        foreach ($args as $arg) {
            // If we're expecting an option value
            if ($waitingForOptionValue) {
                $parsed['options'][$currentOption] = $arg;
                $waitingForOptionValue = false;
                continue;
            }

            // Long option (--option)
            if (substr($arg, 0, 2) === '--') {
                $option = substr($arg, 2);

                // Handle --option=value format
                if (strpos($option, '=') !== false) {
                    list($option, $value) = explode('=', $option, 2);
                    if (isset($this->options[$option])) {
                        $parsed['options'][$option] = $value;
                    }
                } else {
                    if (isset($this->options[$option])) {
                        if ($this->options[$option]['requires_value']) {
                            $waitingForOptionValue = true;
                            $currentOption = $option;
                        } else {
                            $parsed['options'][$option] = true;
                        }
                    }
                }
            }
            // Short option (-o)
            elseif (substr($arg, 0, 1) === '-') {
                $shortcut = substr($arg, 1);

                // Find the option with this shortcut
                foreach ($this->options as $name => $option) {
                    if ($option['shortcut'] === $shortcut) {
                        if ($option['requires_value']) {
                            $waitingForOptionValue = true;
                            $currentOption = $name;
                        } else {
                            $parsed['options'][$name] = true;
                        }
                        break;
                    }
                }
            }
            // It's a positional argument
            else {
                $positionalArgs[] = $arg;
            }
        }

        // Assign positional arguments to named arguments
        $argNames = array_keys($this->arguments);
        foreach ($positionalArgs as $index => $value) {
            if (isset($argNames[$index])) {
                $parsed['arguments'][$argNames[$index]] = $value;
            }
        }

        // Check for required arguments
        foreach ($this->arguments as $name => $argument) {
            if ($argument['required'] && $parsed['arguments'][$name] === null) {
                throw new \Exception("Required argument '$name' is missing.");
            }
        }

        return $parsed;
    }

    /**
     * Show help information for this command
     */
    public function showHelp()
    {
        $content = "Description: " . $this->description . PHP_EOL . PHP_EOL;

        if (!empty($this->arguments)) {
            $content .= "Arguments:" . PHP_EOL;
            foreach ($this->arguments as $name => $argument) {
                $required = $argument['required'] ? ' (required)' : '';
                $default = $argument['default'] !== null ? " [default: " . $argument['default'] . "]" : "";
                $content .= "  " . TermUI::GREEN . $name . TermUI::RESET . ": " . $argument['description'] . $required . $default . PHP_EOL;
            }
            $content .= PHP_EOL;
        }

        if (!empty($this->options)) {
            $content .= "Options:" . PHP_EOL;
            foreach ($this->options as $name => $option) {
                $shortcut = $option['shortcut'] ? ", -" . $option['shortcut'] : '';
                $needsValue = $option['requires_value'] ? " <value>" : "";
                $default = $option['default'] !== null ? " [default: " . $option['default'] . "]" : "";
                $content .= "  " . TermUI::GREEN . "--" . $name . $shortcut . TermUI::RESET . $needsValue . ": " . $option['description'] . $default . PHP_EOL;
            }
        }

        TermUI::box("COMMAND: " . $this->name, $content, TermUI::CYAN);
    }

    /**
     * Ask the user to provide values for missing required arguments interactively
     */
    protected function promptForMissingArguments($parsed)
    {
        foreach ($this->arguments as $name => $argument) {
            if ($argument['required'] && empty($parsed['arguments'][$name])) {
                $parsed['arguments'][$name] = TermUI::prompt("Enter a value for argument '$name'", $argument['default']);
            }
        }
        return $parsed;
    }

    /**
     * Execute the command
     */
    abstract public function handle($arguments, $options);

    /**
     * Run the command with the given arguments
     */
    public function run($args)
    {
        try {
            // Check for help flag
            if (in_array('--help', $args) || in_array('-h', $args)) {
                $this->showHelp();
                return 0;
            }

            $input = $this->parseInput($args);
            $input = $this->promptForMissingArguments($input);

            return $this->handle($input['arguments'], $input['options']);
        } catch (\Exception $e) {
            TermUI::error($e->getMessage());
            return 1;
        }
    }
}