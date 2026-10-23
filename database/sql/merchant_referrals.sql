-- Add merchant referral tracking to an existing Pahatud Agent Portal.
-- MySQL / MariaDB. Select the Pahatud database before running this script.

ALTER TABLE `agents`
    ADD COLUMN `referral_code` VARCHAR(20) NULL AFTER `mobile`,
    ADD UNIQUE INDEX `agents_referral_code_unique` (`referral_code`);

UPDATE `agents`
SET `referral_code` = CONCAT('PAH-', LPAD(UPPER(CONV(`id`, 10, 36)), 8, '0'))
WHERE `referral_code` IS NULL;

ALTER TABLE `merchant_applications`
    ADD COLUMN `agent_id` BIGINT UNSIGNED NULL AFTER `id`,
    ADD INDEX `merchant_applications_agent_id_index` (`agent_id`);

-- The existing partners.agent_id column remains the source used by commission
-- calculations after an approved application is converted into a merchant.

SHOW COLUMNS FROM `agents` LIKE 'referral_code';
SHOW COLUMNS FROM `merchant_applications` LIKE 'agent_id';
