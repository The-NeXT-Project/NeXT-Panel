<?php
namespace TestAddon;

use App\Addon\Hook\AdminHeaderItemHook;

require_once dirname(__DIR__, 2) . '/src/Addon/Hook/AdminHeaderItemHook.php';


AdminHeaderItemHook::getInstance()->addhook(function () {
    return [
        ["name" => "test", "icon" => "rocket", "href" => "/admin/product", "type" => "item"],
        [
            "name" => "test-dropdown",
            "icon" => "rocket",
            "type" => "dropdown",
            "children" => [
                ["name" => "test-child-item1", "icon" => "gift", "href" => "/admin/giftcard", "type" => "item"],
                ["name" => "test-child-item2", "icon" => "friends", "href" => "/admin/invite", "type" => "item"],
                [
                    "name" => "test-child-dropdown",
                    "icon" => "settings",
                    "type" => "dropdown",
                    "children" => [
                        ["name" => "drop-in-drop-item1", "icon" => "users", "href" => "/admin/user"],
                        ["name" => "drop-in-drop-item2", "icon" => "tool", "href" => "/admin/system"],
                    ]
                ],
            ]
        ]
    ];
});
