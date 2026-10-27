-- MySQL / MariaDB. Run once after restaurant_enrollment_documents.sql.
-- Adds editable business information and review status to an existing installation.

ALTER TABLE `partners`
    ADD COLUMN `registered_business_name` VARCHAR(255) NULL,
    ADD COLUMN `tin` VARCHAR(30) NULL,
    ADD COLUMN `business_registration_number` VARCHAR(100) NULL,
    ADD COLUMN `payout_account_name` VARCHAR(255) NULL,
    ADD COLUMN `application_status` VARCHAR(30) NULL,
    ADD COLUMN `application_remarks` TEXT NULL;

ALTER TABLE `restaurant_enrollment_documents`
    ADD COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'pending_verification',
    ADD COLUMN `remarks` TEXT NULL,
    ADD COLUMN `expires_at` DATE NULL,
    ADD COLUMN `reviewed_at` TIMESTAMP NULL DEFAULT NULL;

-- Previously uploaded files must be reviewed; they start Pending Verification.
-- Existing agent-enrolled restaurants retain their current access until edited or reviewed.
