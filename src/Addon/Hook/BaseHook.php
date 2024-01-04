<?php

namespace App\Addon\Hook;

abstract class BaseHook
{
    protected static $instances = array();
    protected function __construct()
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

        if (!isset($instances[$calledClass])) {
            $instances[$calledClass] = new $calledClass();
        }

        return $instances[$calledClass];
    }
    private function __clone()
    {
    }
    public function __wakeup():void
    {
    }
    abstract public function addhook($hook);
    abstract public function runhook();
}
