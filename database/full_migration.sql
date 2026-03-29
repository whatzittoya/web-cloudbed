CREATE TABLE IF NOT EXISTS `access_token` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id` VARCHAR(255) NOT NULL,
    `api_key` VARCHAR(255) NOT NULL,
    `item_id` VARCHAR(32) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @access_token_item_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'access_token'
      AND COLUMN_NAME = 'item_id'
);

SET @add_access_token_item_id := IF(
    @access_token_item_id_exists = 0,
    'ALTER TABLE `access_token` ADD COLUMN `item_id` VARCHAR(32) NULL AFTER `api_key`',
    'SELECT 1'
);

PREPARE add_access_token_item_id_statement FROM @add_access_token_item_id;
EXECUTE add_access_token_item_id_statement;
DEALLOCATE PREPARE add_access_token_item_id_statement;

CREATE TABLE IF NOT EXISTS `tbl_reservation_cloudbed` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `property_id` VARCHAR(32) NOT NULL,
    `reservation_id` VARCHAR(32) NOT NULL,
    `date_created` DATETIME NOT NULL,
    `date_modified` DATETIME NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `guest_id` VARCHAR(32) NOT NULL,
    `profile_id` VARCHAR(32) NOT NULL,
    `guest_name` VARCHAR(255) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `adults` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `children` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `balance` BIGINT NOT NULL DEFAULT 0,
    `source_id` VARCHAR(50) NOT NULL,
    `source_name` VARCHAR(100) NOT NULL,
    `room_type_name` VARCHAR(255) DEFAULT NULL,
    `room_name` VARCHAR(255) DEFAULT NULL,
    `third_party_identifier` VARCHAR(255) DEFAULT NULL,
    `allotment_block_code` VARCHAR(100) DEFAULT NULL,
    `group_code` VARCHAR(100) DEFAULT NULL,
    `origin` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_reservation_reservation_id` (`reservation_id`),
    KEY `idx_reservation_property_id` (`property_id`),
    KEY `idx_reservation_guest_id` (`guest_id`),
    KEY `idx_reservation_profile_id` (`profile_id`),
    KEY `idx_reservation_start_date` (`start_date`),
    KEY `idx_reservation_end_date` (`end_date`),
    KEY `idx_reservation_room_name` (`room_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @reservation_room_type_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_reservation_cloudbed'
      AND COLUMN_NAME = 'room_type_name'
);

SET @add_reservation_room_type := IF(
    @reservation_room_type_exists = 0,
    'ALTER TABLE `tbl_reservation_cloudbed` ADD COLUMN `room_type_name` VARCHAR(255) NULL AFTER `source_name`',
    'SELECT 1'
);

PREPARE add_reservation_room_type_statement FROM @add_reservation_room_type;
EXECUTE add_reservation_room_type_statement;
DEALLOCATE PREPARE add_reservation_room_type_statement;

SET @reservation_room_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_reservation_cloudbed'
      AND COLUMN_NAME = 'room_name'
);

SET @add_reservation_room_name := IF(
    @reservation_room_name_exists = 0,
    'ALTER TABLE `tbl_reservation_cloudbed` ADD COLUMN `room_name` VARCHAR(255) NULL AFTER `room_type_name`',
    'SELECT 1'
);

PREPARE add_reservation_room_name_statement FROM @add_reservation_room_name;
EXECUTE add_reservation_room_name_statement;
DEALLOCATE PREPARE add_reservation_room_name_statement;

SET @reservation_room_name_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_reservation_cloudbed'
      AND INDEX_NAME = 'idx_reservation_room_name'
);

SET @add_reservation_room_name_index := IF(
    @reservation_room_name_index_exists = 0,
    'ALTER TABLE `tbl_reservation_cloudbed` ADD KEY `idx_reservation_room_name` (`room_name`)',
    'SELECT 1'
);

PREPARE add_reservation_room_name_index_statement FROM @add_reservation_room_name_index;
EXECUTE add_reservation_room_name_index_statement;
DEALLOCATE PREPARE add_reservation_room_name_index_statement;

SET @customer_reservation_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_customers'
      AND COLUMN_NAME = 'reservation_id'
);

SET @add_customer_reservation_id := IF(
    @customer_reservation_id_exists = 0,
    'ALTER TABLE `tbl_customers` ADD COLUMN `reservation_id` VARCHAR(32) NULL AFTER `refunded`',
    'SELECT 1'
);

PREPARE add_customer_reservation_id_statement FROM @add_customer_reservation_id;
EXECUTE add_customer_reservation_id_statement;
DEALLOCATE PREPARE add_customer_reservation_id_statement;

SET @customer_reservation_id_nullable := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_customers'
      AND COLUMN_NAME = 'reservation_id'
      AND IS_NULLABLE = 'YES'
);

SET @make_customer_reservation_id_nullable := IF(
    @customer_reservation_id_nullable = 0,
    'ALTER TABLE `tbl_customers` MODIFY COLUMN `reservation_id` VARCHAR(32) NULL',
    'SELECT 1'
);

PREPARE make_customer_reservation_id_nullable_statement FROM @make_customer_reservation_id_nullable;
EXECUTE make_customer_reservation_id_nullable_statement;
DEALLOCATE PREPARE make_customer_reservation_id_nullable_statement;

SET @customer_reservation_id_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbl_customers'
      AND INDEX_NAME = 'uniq_tbl_customers_reservation_id'
);

SET @drop_customer_reservation_id_unique := IF(
    @customer_reservation_id_unique_exists = 1,
    'ALTER TABLE `tbl_customers` DROP INDEX `uniq_tbl_customers_reservation_id`',
    'SELECT 1'
);

PREPARE drop_customer_reservation_id_unique_statement FROM @drop_customer_reservation_id_unique;
EXECUTE drop_customer_reservation_id_unique_statement;
DEALLOCATE PREPARE drop_customer_reservation_id_unique_statement;
