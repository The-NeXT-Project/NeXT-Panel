<?php

declare(strict_types=1);

namespace App\Addon;

final class AddonManager
{
    public array $addon_array = [];
    private static $instance;
    private function __construct()
    {
    }
    private function __clone()
    {
    }
    public static function getInstance()
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function loadAll(): void
    {
        $addon_dir = dirname(__DIR__, 2) . '/addon';
        $this->addon_array = [];
        $data = scandir($addon_dir);
        foreach ($data as $entry) {
            $entry_path = $addon_dir . '/' . $entry;
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($entry_path)) {
                $this->addon_array[$entry] = $entry_path;
            }
        }
        foreach ($this->addon_array as $addon) {
            require_once '' . $addon . '/index.php';
        }
    }
}
