-- ---------------------------------------------------------------------------
-- modules/repair/install/database.sql — ตารางที่โมดูล repair เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
-- ประกาศสองที่ = ติดตั้งใหม่ล้มด้วย "Table already exists" และนิยามสองชุด
-- จะค่อย ๆ ต่างกันจนไซต์ที่อัปเกรดคนละเส้นทางได้สคีมาไม่เหมือนกัน
--
-- ทั้งการติดตั้งใหม่ (common.php::schemaFiles) และการปรับรุ่น (ensureTable)
-- อ่านนิยามจากไฟล์นี้ไฟล์เดียว
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_repair` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`customer_id` int(11) NOT NULL,
`product_no` varchar(150) NOT NULL,
`job_id` varchar(20) NOT NULL,
`job_description` varchar(1000) NOT NULL,
`created_at` datetime NOT NULL,
`appointment_date` date DEFAULT NULL,
`repair_no` varchar(50) DEFAULT NULL,
`informer` varchar(150) DEFAULT NULL,
`appraiser` float NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
UNIQUE KEY `job_id` (`job_id`),
KEY `product_no` (`product_no`),
KEY `idx_customer` (`customer_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_repair_status` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`repair_id` int(11) NOT NULL,
`status` tinyint(2) NOT NULL,
`operator_id` int(11) NOT NULL DEFAULT 0,
`comment` varchar(1000) DEFAULT NULL,
`member_id` int(11) NOT NULL,
`created_at` datetime NOT NULL,
`cost` float NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `idx_repair` (`repair_id`,`id`),
KEY `idx_status` (`status`,`created_at`),
KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- ข้อมูลตั้งต้นของโมดูลนี้
--
-- ⚠️ ต้องอยู่ที่นี่ ไม่ใช่ใน install/database.sql ของโปรเจ็ค เพราะ schemaFiles()
-- รันไฟล์ของโปรเจ็ค **ก่อน** ไฟล์ของโมดูล — INSERT ที่อยู่ฝั่งโปรเจ็คจะวิ่งไปหา
-- ตารางที่ยังไม่ถูกสร้าง แล้วการติดตั้งใหม่ล้มทันที
-- ---------------------------------------------------------------------------

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `is_active`) VALUES
('repairstatus', '1', 'แจ้งซ่อม', '#660000', 1),
('repairstatus', '2', 'กำลังดำเนินการ', '#120eeb', 1),
('repairstatus', '3', 'รออะไหล่', '#d940ff', 1),
('repairstatus', '4', 'ซ่อมสำเร็จ', '#06d628', 1),
('repairstatus', '5', 'ซ่อมไม่สำเร็จ', '#FF0000', 1),
('repairstatus', '6', 'ยกเลิกการซ่อม', '#FF6F00', 1),
('repairstatus', '7', 'ส่งมอบเรียบร้อย', '#000000', 1),
('category_id', '1', 'เครื่องใช้ไฟฟ้า', NULL, 1),
('category_id', '2', 'วัสดุสำนักงาน', NULL, 1),
('category_id', '3', 'Ram', NULL, 1),
('type_id', '1', 'เครื่องคอมพิวเตอร์', NULL, 1),
('type_id', '2', 'เครื่องพิมพ์', NULL, 1),
('type_id', '3', 'โปรเจ็คเตอร์', NULL, 1),
('type_id', '4', 'จอมอนิเตอร์', NULL, 1),
('model_id', '1', 'Apple', NULL, 1),
('model_id', '2', 'Asus', NULL, 1),
('model_id', '3', 'Cannon', NULL, 1),
('model_id', '4', 'ACER', NULL, 1),
('unit', 'อัน', 'อัน', NULL, 1),
('unit', 'กล่อง', 'กล่อง', NULL, 1),
('unit', 'เครื่อง', 'เครื่อง', NULL, 1);
