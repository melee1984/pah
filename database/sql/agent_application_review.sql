-- Add agent application review fields to an existing Pahatud Agent Portal.
-- MySQL / MariaDB. Select the Pahatud database before running this script.

ALTER TABLE `agents`
    ADD COLUMN `review_status` VARCHAR(20) NULL AFTER `active`,
    ADD COLUMN `review_message` TEXT NULL AFTER `review_status`,
    ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL AFTER `review_message`,
    ADD INDEX `agents_review_status_index` (`review_status`);

-- Existing inactive agents remain pending review (review_status IS NULL).
-- Existing active agents retain access and are not treated as pending.
