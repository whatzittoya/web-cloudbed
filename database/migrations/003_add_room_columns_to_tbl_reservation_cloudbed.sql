ALTER TABLE `tbl_reservation_cloudbed`
    ADD COLUMN `room_type_name` VARCHAR(255) DEFAULT NULL AFTER `source_name`,
    ADD COLUMN `room_name` VARCHAR(255) DEFAULT NULL AFTER `room_type_name`,
    ADD KEY `idx_reservation_room_name` (`room_name`);
