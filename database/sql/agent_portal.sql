-- Pahatud Agent Portal database schema
-- MySQL / MariaDB
--
-- This is the SQL equivalent of:
-- database/migrations/2026_08_27_000000_create_agent_portal_tables.php
-- database/migrations/2026_08_27_000001_create_restaurant_invitations_table.php
--
-- Select the Pahatud database before running this script, for example:
-- USE `pahatud`;

CREATE TABLE IF NOT EXISTS `agents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `mobile` VARCHAR(30) NULL,
    `password` VARCHAR(255) NOT NULL,
    `commission_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 30.00,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `remember_token` VARCHAR(100) NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `agents_email_unique` (`email`),
    KEY `agents_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add partners.agent_id only when the partners table exists and the column
-- has not already been added. No foreign key is used because partners is a
-- legacy Pahatud table that is not reproduced by Laravel migrations.
SET @partners_table_exists = (
    SELECT COUNT(*)
    FROM `information_schema`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'partners'
);

SET @partners_agent_id_exists = (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'partners'
      AND `COLUMN_NAME` = 'agent_id'
);

SET @add_partners_agent_id_sql = IF(
    @partners_table_exists = 1 AND @partners_agent_id_exists = 0,
    'ALTER TABLE `partners` ADD COLUMN `agent_id` BIGINT UNSIGNED NULL, ADD INDEX `partners_agent_id_index` (`agent_id`)',
    'SELECT ''partners.agent_id already exists, or the partners table is unavailable'' AS `message`'
);

PREPARE add_partners_agent_id_statement FROM @add_partners_agent_id_sql;
EXECUTE add_partners_agent_id_statement;
DEALLOCATE PREPARE add_partners_agent_id_statement;

CREATE TABLE IF NOT EXISTS `agent_commissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `restaurant_id` BIGINT UNSIGNED NOT NULL,
    `agent_id` BIGINT UNSIGNED NOT NULL,
    `order_amount` DECIMAL(12, 2) NOT NULL,
    `commission_percentage` DECIMAL(5, 2) NOT NULL,
    `commission_amount` DECIMAL(12, 2) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `qualified_at` TIMESTAMP NOT NULL,
    `reversed_at` TIMESTAMP NULL DEFAULT NULL,
    `reversal_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `agent_commissions_order_id_unique` (`order_id`),
    KEY `agent_commissions_restaurant_id_index` (`restaurant_id`),
    KEY `agent_commissions_agent_id_index` (`agent_id`),
    KEY `agent_commissions_status_index` (`status`),
    KEY `agent_commissions_qualified_at_index` (`qualified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurant_invitations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `restaurant_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `accepted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `restaurant_invitations_restaurant_id_unique` (`restaurant_id`),
    UNIQUE KEY `restaurant_invitations_token_hash_unique` (`token_hash`),
    KEY `restaurant_invitations_user_id_index` (`user_id`),
    KEY `restaurant_invitations_email_index` (`email`),
    KEY `restaurant_invitations_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional verification queries:
SHOW COLUMNS FROM `agents`;
SHOW COLUMNS FROM `partners` LIKE 'agent_id';
SHOW COLUMNS FROM `agent_commissions`;
SHOW COLUMNS FROM `restaurant_invitations`;
