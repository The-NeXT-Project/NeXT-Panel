<?php

declare(strict_types=1);

namespace App\Addon\Hook;

abstract class BaseHook
{
    protected static $instances = [];
    protected function __construct()
    {
    }
    private function __clone()
    {
    }
    public function __wakeup(): void
    {
    }

    final public static function getInstance()
    {
        $calledClass = get_called_class();

        return self::getInstanceByName($calledClass);
    }
    final public static function getInstanceByName($name)
    {
        $calledClass = $name;

        if (! isset(self::$instances[$calledClass])) {
            self::$instances[$calledClass] = new $calledClass();
        }

        return self::$instances[$calledClass];
    }
    abstract public function addhook($hook);
    abstract public function runhook();
}
