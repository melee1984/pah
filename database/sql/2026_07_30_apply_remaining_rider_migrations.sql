-- Apply the three pending rider migrations without invoking Laravel's migrator.
-- Target: MySQL 8 / MariaDB, using Laravel's configured utf8mb4 collation.
--
-- Equivalent migrations:
--   2026_07_30_000000_create_rider_applications_table
--   2026_07_30_000001_add_staged_flow_to_rider_applications
--   2026_07_30_000002_create_rider_api_tables
--
-- The first two migrations are represented by their combined final schema.
-- CREATE TABLE IF NOT EXISTS makes the script safe to resume if it is interrupted
-- between tables. The migration ledger is updated only after all DDL completes.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Rider applications (final schema after migrations 000000 and 000001)

CREATE TABLE IF NOT EXISTS `rider_applications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `access_token_hash` CHAR(64) NULL,
    `full_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NOT NULL,
    `mobile` VARCHAR(25) NULL,
    `password` VARCHAR(255) NOT NULL,
    `birth_date` DATE NULL,
    `home_address` TEXT NULL,
    `profile_photo_path` VARCHAR(255) NULL,
    `emergency_contact_name` VARCHAR(255) NULL,
    `emergency_contact_relationship` VARCHAR(100) NULL,
    `emergency_contact_mobile` VARCHAR(25) NULL,
    `government_id_path` VARCHAR(255) NULL,
    `drivers_license_path` VARCHAR(255) NULL,
    `vehicle_registration_path` VARCHAR(255) NULL,
    `vehicle_type` VARCHAR(100) NULL,
    `vehicle_make_model` VARCHAR(255) NULL,
    `vehicle_plate_number` VARCHAR(50) NULL,
    `vehicle_color` VARCHAR(100) NULL,
    `payout_method` VARCHAR(100) NULL,
    `payout_account_name` VARCHAR(255) NULL,
    `payout_account_number` TEXT NULL,
    `status` VARCHAR(25) NOT NULL DEFAULT 'draft',
    `review_notes` TEXT NULL,
    `submitted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_applications_reference_unique` (`reference`),
    UNIQUE KEY `rider_applications_access_token_hash_unique` (`access_token_hash`),
    UNIQUE KEY `rider_applications_email_unique` (`email`),
    KEY `rider_applications_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_application_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_application_id` BIGINT UNSIGNED NOT NULL,
    `reference` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `size_bytes` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_application_documents_reference_unique` (`reference`),
    UNIQUE KEY `rider_application_documents_rider_application_id_type_unique`
        (`rider_application_id`, `type`),
    CONSTRAINT `rider_application_documents_rider_application_id_foreign`
        FOREIGN KEY (`rider_application_id`) REFERENCES `rider_applications` (`id`)
        ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Legacy rider table. The existing table is intentionally left unchanged.

CREATE TABLE IF NOT EXISTS `rider` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(250) NULL,
    `date_join` DATETIME NULL,
    `license_no` INT NULL,
    `mobile` VARCHAR(15) NULL,
    `active` TINYINT(1) NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `rider_user_id_index` (`user_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Rider API schema (migration 000002)

CREATE TABLE IF NOT EXISTS `rider_api_devices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `personal_access_token_id` BIGINT UNSIGNED NULL,
    `device_key` VARCHAR(255) NOT NULL,
    `push_token` TEXT NULL,
    `platform` VARCHAR(30) NULL,
    `device_model` VARCHAR(255) NULL,
    `app_version` VARCHAR(30) NULL,
    `last_seen_at` TIMESTAMP NULL,
    `revoked_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_devices_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_devices_rider_id_device_key_unique` (`rider_id`, `device_key`),
    KEY `rider_api_devices_rider_id_index` (`rider_id`),
    KEY `rider_api_devices_personal_access_token_id_index` (`personal_access_token_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_availability` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `state` VARCHAR(30) NOT NULL DEFAULT 'offline',
    `schedule` JSON NULL,
    `zone_preferences` JSON NULL,
    `heartbeat_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_availability_rider_id_unique` (`rider_id`),
    KEY `rider_api_availability_state_index` (`state`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_zones` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `boundary` JSON NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_zones_reference_unique` (`reference`),
    KEY `rider_api_zones_active_index` (`active`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_locations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `delivery_reference` CHAR(36) NULL,
    `latitude` DECIMAL(10, 7) NOT NULL,
    `longitude` DECIMAL(10, 7) NOT NULL,
    `accuracy_meters` DECIMAL(8, 2) NULL,
    `heading` DECIMAL(6, 2) NULL,
    `speed_mps` DECIMAL(8, 2) NULL,
    `recorded_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `rider_api_locations_rider_id_index` (`rider_id`),
    KEY `rider_api_locations_delivery_reference_index` (`delivery_reference`),
    KEY `rider_api_locations_recorded_at_index` (`recorded_at`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_otp_challenges` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `purpose` VARCHAR(30) NOT NULL,
    `channel` VARCHAR(20) NOT NULL,
    `destination` VARCHAR(255) NOT NULL,
    `code_hash` CHAR(64) NOT NULL,
    `verification_token_hash` CHAR(64) NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` TIMESTAMP NOT NULL,
    `verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_otp_challenges_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_otp_challenges_verification_token_hash_unique`
        (`verification_token_hash`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NULL,
    `legacy_order_id` BIGINT UNSIGNED NULL,
    `legacy_booking_id` BIGINT UNSIGNED NULL,
    `current_state` VARCHAR(50) NOT NULL DEFAULT 'offered',
    `merchant_name` VARCHAR(255) NULL,
    `pickup_area` VARCHAR(255) NULL,
    `pickup_address` TEXT NULL,
    `pickup_latitude` DECIMAL(10, 7) NULL,
    `pickup_longitude` DECIMAL(10, 7) NULL,
    `dropoff_area` VARCHAR(255) NULL,
    `dropoff_address` TEXT NULL,
    `dropoff_latitude` DECIMAL(10, 7) NULL,
    `dropoff_longitude` DECIMAL(10, 7) NULL,
    `customer_name` VARCHAR(255) NULL,
    `customer_mobile` VARCHAR(255) NULL,
    `distance_meters` INT UNSIGNED NULL,
    `eta_seconds` INT UNSIGNED NULL,
    `earnings_centavos` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `cod_centavos` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `order_count` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_batched` TINYINT(1) NOT NULL DEFAULT 0,
    `pickup_code_hash` CHAR(64) NULL,
    `customer_code_hash` CHAR(64) NULL,
    `accepted_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_deliveries_reference_unique` (`reference`),
    KEY `rider_api_deliveries_rider_id_index` (`rider_id`),
    KEY `rider_api_deliveries_legacy_order_id_index` (`legacy_order_id`),
    KEY `rider_api_deliveries_legacy_booking_id_index` (`legacy_booking_id`),
    KEY `rider_api_deliveries_current_state_index` (`current_state`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_offers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `decline_reason` VARCHAR(255) NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `responded_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_offers_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_offers_rider_id_delivery_id_unique` (`rider_id`, `delivery_id`),
    KEY `rider_api_offers_rider_id_index` (`rider_id`),
    KEY `rider_api_offers_status_index` (`status`),
    KEY `rider_api_offers_expires_at_index` (`expires_at`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_delivery_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `event_id` CHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `latitude` DECIMAL(10, 7) NULL,
    `longitude` DECIMAL(10, 7) NULL,
    `metadata` JSON NULL,
    `occurred_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_delivery_events_event_id_unique` (`event_id`),
    KEY `rider_api_delivery_events_delivery_id_index` (`delivery_id`),
    KEY `rider_api_delivery_events_type_index` (`type`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_proof_uploads` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `upload_token_hash` CHAR(64) NOT NULL,
    `method` VARCHAR(20) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_proof_uploads_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_proof_uploads_upload_token_hash_unique` (`upload_token_hash`),
    KEY `rider_api_proof_uploads_delivery_id_index` (`delivery_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_delivery_proofs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `method` VARCHAR(20) NOT NULL,
    `path` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `processing_status` VARCHAR(20) NOT NULL DEFAULT 'complete',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_delivery_proofs_reference_unique` (`reference`),
    KEY `rider_api_delivery_proofs_delivery_id_index` (`delivery_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_delivery_issues` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_delivery_issues_reference_unique` (`reference`),
    KEY `rider_api_delivery_issues_delivery_id_index` (`delivery_id`),
    KEY `rider_api_delivery_issues_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_delivery_calls` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `party` VARCHAR(20) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_delivery_calls_reference_unique` (`reference`),
    KEY `rider_api_delivery_calls_delivery_id_index` (`delivery_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_share_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `delivery_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `revoked_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_share_links_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_share_links_token_hash_unique` (`token_hash`),
    KEY `rider_api_share_links_delivery_id_index` (`delivery_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_wallets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `available_centavos` BIGINT NOT NULL DEFAULT 0,
    `pending_centavos` BIGINT NOT NULL DEFAULT 0,
    `cash_collected_centavos` BIGINT NOT NULL DEFAULT 0,
    `amount_owed_centavos` BIGINT NOT NULL DEFAULT 0,
    `daily_cod_limit_centavos` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_wallets_rider_id_unique` (`rider_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_wallet_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(40) NOT NULL,
    `amount_centavos` BIGINT NOT NULL,
    `balance_after_centavos` BIGINT NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `related_type` VARCHAR(40) NULL,
    `related_reference` CHAR(36) NULL,
    `occurred_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_wallet_transactions_reference_unique` (`reference`),
    KEY `rider_api_wallet_transactions_rider_id_index` (`rider_id`),
    KEY `rider_api_wallet_transactions_type_index` (`type`),
    KEY `rider_api_wallet_transactions_occurred_at_index` (`occurred_at`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_cod_remittances` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `amount_centavos` BIGINT UNSIGNED NOT NULL,
    `proof_path` VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `review_notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_cod_remittances_reference_unique` (`reference`),
    KEY `rider_api_cod_remittances_rider_id_index` (`rider_id`),
    KEY `rider_api_cod_remittances_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_payout_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `method` VARCHAR(50) NOT NULL,
    `account_name` VARCHAR(255) NOT NULL,
    `account_number` TEXT NOT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_payout_accounts_reference_unique` (`reference`),
    KEY `rider_api_payout_accounts_rider_id_index` (`rider_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_withdrawals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `payout_account_id` BIGINT UNSIGNED NOT NULL,
    `amount_centavos` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_withdrawals_reference_unique` (`reference`),
    KEY `rider_api_withdrawals_rider_id_index` (`rider_id`),
    KEY `rider_api_withdrawals_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_wallet_disputes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `transaction_reference` CHAR(36) NULL,
    `type` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_wallet_disputes_reference_unique` (`reference`),
    KEY `rider_api_wallet_disputes_rider_id_index` (`rider_id`),
    KEY `rider_api_wallet_disputes_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `delivery_reference` CHAR(36) NULL,
    `subject` VARCHAR(255) NULL,
    `closed_at` TIMESTAMP NULL,
    `last_message_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_conversations_reference_unique` (`reference`),
    KEY `rider_api_conversations_rider_id_index` (`rider_id`),
    KEY `rider_api_conversations_delivery_reference_index` (`delivery_reference`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `client_message_id` CHAR(36) NOT NULL,
    `sender_type` VARCHAR(20) NOT NULL,
    `body` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'sent',
    `delivered_at` TIMESTAMP NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_messages_reference_unique` (`reference`),
    UNIQUE KEY `rider_api_messages_client_message_id_unique` (`client_message_id`),
    KEY `rider_api_messages_conversation_id_index` (`conversation_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_message_attachments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `message_id` BIGINT UNSIGNED NULL,
    `path` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `size_bytes` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_message_attachments_reference_unique` (`reference`),
    KEY `rider_api_message_attachments_conversation_id_index` (`conversation_id`),
    KEY `rider_api_message_attachments_message_id_index` (`message_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `deep_link` VARCHAR(255) NULL,
    `data` JSON NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_notifications_reference_unique` (`reference`),
    KEY `rider_api_notifications_rider_id_index` (`rider_id`),
    KEY `rider_api_notifications_type_index` (`type`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_notification_preferences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `delivery_offers` TINYINT(1) NOT NULL DEFAULT 1,
    `delivery_updates` TINYINT(1) NOT NULL DEFAULT 1,
    `wallet_updates` TINYINT(1) NOT NULL DEFAULT 1,
    `support_messages` TINYINT(1) NOT NULL DEFAULT 1,
    `marketing` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_notification_preferences_rider_id_unique` (`rider_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `language` VARCHAR(10) NOT NULL DEFAULT 'en',
    `navigation_app` VARCHAR(30) NOT NULL DEFAULT 'system',
    `share_live_location` TINYINT(1) NOT NULL DEFAULT 1,
    `background_location` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_settings_rider_id_unique` (`rider_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_feedback` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `delivery_reference` CHAR(36) NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_feedback_reference_unique` (`reference`),
    KEY `rider_api_feedback_rider_id_index` (`rider_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rider_api_delete_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` CHAR(36) NOT NULL,
    `rider_id` BIGINT UNSIGNED NOT NULL,
    `reason` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `cancelled_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rider_api_delete_requests_reference_unique` (`reference`),
    KEY `rider_api_delete_requests_rider_id_index` (`rider_id`),
    KEY `rider_api_delete_requests_status_index` (`status`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Keep Laravel's migration ledger in sync. All three entries share one new batch,
-- matching what a single `php artisan migrate` run would record.

SET @rider_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_000000_create_rider_applications_table', @rider_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_07_30_000000_create_rider_applications_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_000001_add_staged_flow_to_rider_applications', @rider_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_07_30_000001_add_staged_flow_to_rider_applications'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_000002_create_rider_api_tables', @rider_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_07_30_000002_create_rider_api_tables'
);
