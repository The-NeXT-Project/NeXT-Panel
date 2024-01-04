<?php

declare(strict_types=1);

namespace App\Addon\Hook;

final class AdminHeaderItemHook extends BaseHook
{
    // hooks: array of function
    private static $hooks = [];
    public function addhook($hook): void
    {
        if (! is_null(self::$hooks)) {
            self::$hooks = [];
        }
        array_push(self::$hooks, $hook);
    }
    public function runhook()
    {
        $hook_re = [];
        // iter the hooks
        foreach (self::$hooks as $hook) {
            $re = call_user_func($hook);
            $hook_re = array_merge($hook_re, $re);
        }
        return $hook_re;
    }
}
