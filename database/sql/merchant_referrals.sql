-- Add merchant referral tracking to an existing Pahatud Agent Portal.
-- MySQL / MariaDB. Select the Pahatud database before running this script.

ALTER TABLE `agents`
    ADD COLUMN `referral_code` VARCHAR(20) NULL AFTER `mobile`,
    ADD UNIQUE INDEX `agents_referral_code_unique` (`referral_code`);

UPDATE `agents`
SET `referral_code` = CONCAT('PAH-', LPAD(UPPER(CONV(`id`, 10, 36)), 8, '0'))
WHERE `referral_code` IS NULL;

-- Merchant applications are stored directly in partners. The existing
-- partners.agent_id column is also the source used by commission calculations.

SHOW COLUMNS FROM `agents` LIKE 'referral_code';
SHOW COLUMNS FROM `partners` LIKE 'agent_id';
