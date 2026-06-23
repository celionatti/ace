<?php

namespace App\Middlewares;

abstract class BaseMiddleware
{
    protected array $actions = [];

    /**
     * @param array $actions Actions this middleware applies to. Empty applies to all actions.
     */
    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * Check if middleware matches current action and run it
     */
    public function execute(string $action): void
    {
        if (empty($this->actions) || in_array($action, $this->actions)) {
            $this->run();
        }
    }

    /**
     * Implement validation checks or redirection inside subclasses
     */
    abstract protected function run(): void;
}

