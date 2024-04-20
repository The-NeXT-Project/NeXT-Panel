<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            CREATE TABLE `mfa_credential` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `userid` int(11) unsigned NOT NULL COMMENT '用户ID',
                `body` text NOT NULL COMMENT '密钥内容',
                `name` varchar(255) DEFAULT NULL COMMENT '设备名称',
                `rawid` varchar(255) DEFAULT NULL COMMENT '设备ID',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                `used_at` datetime DEFAULT NULL COMMENT '上次使用时间',
                `type` varchar(255) NOT NULL COMMENT '类型',
                PRIMARY KEY (`id`),
                KEY `userid` (`userid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        DB::getPdo()->exec("
            INSERT INTO `mfa_credential` (`userid`, `body`, `type`)
            SELECT `id`, `ga_token`, 'totp'
            FROM `user`
            WHERE `ga_enable` = 1;
        ");

        DB::getPdo()->exec('
            ALTER TABLE `user` 
            DROP COLUMN `ga_enable`,
            DROP COLUMN `ga_token`;
        ');

        return 2024041800;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            ALTER TABLE `user` 
            ADD COLUMN `ga_enable` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'GA开关',
            ADD COLUMN `ga_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'GA密钥';
        ");

        DB::getPdo()->exec('
            DROP TABLE IF EXISTS `mfa_credential`;
        ');

        return 2024040500;
    }
};
