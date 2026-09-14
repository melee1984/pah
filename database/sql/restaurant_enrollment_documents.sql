-- MySQL / MariaDB. Run once against the Pahatud database before enabling
-- restaurant document uploads.

ALTER TABLE `partners`
    ADD COLUMN `business_structure` VARCHAR(30) NULL,
    ADD COLUMN `enrolling_as` VARCHAR(30) NULL;

CREATE TABLE IF NOT EXISTS `restaurant_enrollment_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `partner_id` BIGINT UNSIGNED NOT NULL,
    `document_type` VARCHAR(50) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `restaurant_enrollment_documents_partner_type_unique` (`partner_id`, `document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
