<?php

declare(strict_types=1);

/**
 * =======================================
 * ***************************************
 * =========== HookManager Class =========
 * ***************************************
 * =======================================
 */

namespace Celionatti\Ace\HookManager;

class Hook
{
    private static $actions = [];
    private static $filters = [];

    /**
     * Add an action hook.
     *
     * @param string $hook - The name of the hook.
     * @param callable $callback - The function to attach.
     * @param int $priority - The priority of the hook (lower numbers run earlier).
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        self::$actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute an action hook.
     *
     * @param string $hook - The name of the hook.
     * @param mixed ...$args - Any arguments to pass to the callbacks.
     */
    public static function doAction(string $hook, ...$args): void
    {
        if (isset(self::$actions[$hook])) {
            foreach (self::getSortedHooks(self::$actions[$hook]) as $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func_array($callback, $args);
                }
            }
        }
    }

    /**
     * Add a filter hook.
     *
     * @param string $hook - The name of the filter.
     * @param callable $callback - The function to modify the value.
     * @param int $priority - The priority of the hook (lower numbers run earlier).
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        self::$filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply a filter hook.
     *
     * @param string $hook - The name of the filter.
     * @param mixed $value - The value to be filtered.
     * @param mixed ...$args - Any additional arguments to pass to the callbacks.
     * @return mixed - The filtered value.
     */
    public static function applyFilter(string $hook, $value, ...$args)
    {
        if (isset(self::$filters[$hook])) {
            foreach (self::getSortedHooks(self::$filters[$hook]) as $callbacks) {
                foreach ($callbacks as $callback) {
                    $value = call_user_func_array($callback, array_merge([$value], $args));
                }
            }
        }
        return $value;
    }

    /**
     * Sort hooks by priority.
     *
     * @param array $hooks - The hooks to sort.
     * @return array - Sorted hooks.
     */
    private static function getSortedHooks(array $hooks): array
    {
        ksort($hooks);
        return $hooks;
    }
}