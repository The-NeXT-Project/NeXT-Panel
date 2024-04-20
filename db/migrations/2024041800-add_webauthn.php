<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            CREATE TABLE `webauthn_devices` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `userid` int(11) unsigned NOT NULL COMMENT '用户ID',
                `body` text NOT NULL COMMENT '密钥内容',
                `name` varchar(255) DEFAULT NULL COMMENT '设备名称',
                `rawid` varchar(255) DEFAULT NULL COMMENT '设备ID',
                `created_at` datetime NOT NULL COMMENT '创建时间',
                `used_at` datetime DEFAULT NULL COMMENT '上次使用时间',
                PRIMARY KEY (`id`),
                KEY `userid` (`userid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return 2024041800;
    }

    public function down(): int
    {
        DB::getPdo()->exec('
            DROP TABLE IF EXISTS `webauthn_devices`;
        ');

        return 2024040500;
    }
};
