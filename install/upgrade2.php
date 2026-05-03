<?php
if (defined('ROOT_PATH')) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        include ROOT_PATH.'install/upgrade1.php';
    } else {
        $error = false;
        // Database Class
        include ROOT_PATH.'install/db.php';
        // ค่าติดตั้งฐานข้อมูล
        $db_config = include ROOT_PATH.'settings/database.php';
        try {
            $db_config = $db_config['mysql'];
            // เขื่อมต่อฐานข้อมูล
            $db = new Db($db_config);
        } catch (\Exception $exc) {
            $error = true;
            echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
            echo '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้</p>';
            echo '<p>อาจเป็นไปได้ว่า</p>';
            echo '<ol>';
            echo '<li>เซิร์ฟเวอร์ของฐานข้อมูลของคุณไม่สามารถใช้งานได้ในขณะนี้</li>';
            echo '<li>ค่ากำหนดของฐานข้อมูลไม่ถูกต้อง (ตรวจสอบไฟล์ settings/database.php)</li>';
            echo '<li>ไม่พบฐานข้อมูลที่ต้องการติดตั้ง กรุณาสร้างฐานข้อมูลก่อน หรือใช้ฐานข้อมูลที่มีอยู่แล้ว</li>';
            echo '<li class="incorrect">'.$exc->getMessage().'</li>';
            echo '</ol>';
            echo '<p>หากคุณไม่สามารถดำเนินการแก้ไขข้อผิดพลาดด้วยตัวของคุณเองได้ ให้ติดต่อผู้ดูแลระบบเพื่อขอข้อมูลที่ถูกต้อง หรือ ลองติดตั้งใหม่</p>';
            echo '<p class="submit"><a href="index.php?step=1" class="btn large btn-secondary">กลับไปลองใหม่</a></p>';
        }
        if (!$error) {
            // เชื่อมต่อฐานข้อมูลสำเร็จ
            $content = ['<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>'];
            try {
                // =========================================================
                // user
                // =========================================================
                $table_user = $db_config['prefix'].'_user';
                if (empty($config['password_key'])) {
                    // อัปเดตข้อมูลผู้ดูแลระบบ
                    $config['password_key'] = uniqid();
                }
                // ตรวจสอบการ login
                updateAdmin($db, $table_user, $_POST['username'], $_POST['password'], $config['password_key']);

                foreach (['username', 'token', 'id_card', 'phone', 'activatecode', 'line_uid', 'telegram_id', 'status'] as $_idx) {
                    if ($db->indexExists($table_user, $_idx)) {
                        $db->query("ALTER TABLE `$table_user` DROP INDEX `$_idx`");
                    }
                }

                // rename create_date → created_at
                if ($db->fieldExists($table_user, 'create_date')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `create_date` `created_at` DATETIME NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เปลี่ยนชื่อ create_date → created_at</li>';
                }
                // activatecode: varchar(32) NOT NULL → varchar(64) NULL
                if (!$db->isColumnType($table_user, 'activatecode', 'varchar(64)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `activatecode` `activatecode` VARCHAR(64) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข activatecode เป็น VARCHAR(64) NULL</li>';
                }
                // address: varchar(150) → varchar(64)
                if (!$db->isColumnType($table_user, 'address', 'varchar(64)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `address` `address` VARCHAR(64) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข address เป็น VARCHAR(64)</li>';
                }
                // password: varchar(50) → varchar(64)
                if (!$db->isColumnType($table_user, 'password', 'varchar(64)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `password` `password` VARCHAR(64) NOT NULL");
                    $content[] = '<li class="correct">user: แก้ไข password เป็น VARCHAR(64)</li>';
                }
                // permission: text → TEXT
                if (!$db->isColumnType($table_user, 'permission', 'text')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `permission` `permission` TEXT NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข permission เป็น TEXT</li>';
                }
                // phone: varchar(32) → varchar(20)
                if (!$db->isColumnType($table_user, 'phone', 'varchar(20)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `phone` `phone` VARCHAR(20) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข phone เป็น VARCHAR(20)</li>';
                }
                // province: varchar(50) → varchar(64)
                if (!$db->isColumnType($table_user, 'province', 'varchar(64)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `province` `province` VARCHAR(64) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข province เป็น VARCHAR(64)</li>';
                }
                // provinceID: varchar(3) → smallint(3)
                if (!$db->isColumnType($table_user, 'provinceID', 'smallint(3)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `provinceID` `provinceID` SMALLINT(3) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข provinceID เป็น SMALLINT(3)</li>';
                }
                // salt: allow null
                if (!$db->isColumnType($table_user, 'salt', 'varchar(32)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `salt` `salt` VARCHAR(32) NOT NULL DEFAULT ''");
                    $content[] = '<li class="correct">user: แก้ไข salt เป็น NOT NULL DEFAULT \'\'</li>';
                }
                // social: tinyint → enum (migrate 0 → 'user' first)
                if ($db->isColumnType($table_user, 'social', 'tinyint')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `social` `social` VARCHAR(32) NULL DEFAULT NULL");
                    $db->query("UPDATE `$table_user` SET `social` = 'user' WHERE `social` = 0 OR `social` IS NULL");
                    $db->query("UPDATE `$table_user` SET `social` = 'facebook' WHERE `social` = 1");
                    $db->query("UPDATE `$table_user` SET `social` = 'google' WHERE `social` = 2");
                    $db->query("UPDATE `$table_user` SET `social` = 'line' WHERE `social` = 3");
                    $db->query("UPDATE `$table_user` SET `social` = 'telegram' WHERE `social` = 4");
                    $db->query("ALTER TABLE `$table_user` CHANGE `social` `social` ENUM('user','facebook','google','line','telegram') NULL DEFAULT 'user'");
                    $content[] = '<li class="correct">user: แก้ไข social เป็น ENUM</li>';
                }
                // telegram_id: varchar(13) → varchar(20)
                if (!$db->isColumnType($table_user, 'telegram_id', 'varchar(20)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `telegram_id` `telegram_id` VARCHAR(20) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข telegram_id เป็น VARCHAR(20)</li>';
                }
                // token: varchar(50) → varchar(512)
                if (!$db->isColumnType($table_user, 'token', 'varchar(512)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `token` `token` VARCHAR(512) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข token เป็น VARCHAR(512)</li>';
                }
                // zipcode: varchar(10) → varchar(5)
                if (!$db->isColumnType($table_user, 'zipcode', 'varchar(5)')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `zipcode` `zipcode` VARCHAR(5) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: แก้ไข zipcode เป็น VARCHAR(5)</li>';
                }
                // add new columns
                if (!$db->fieldExists($table_user, 'address2')) {
                    $db->query("ALTER TABLE `$table_user` ADD `address2` VARCHAR(64) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม address2</li>';
                }
                if (!$db->fieldExists($table_user, 'birthday')) {
                    $db->query("ALTER TABLE `$table_user` ADD `birthday` DATE NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม birthday</li>';
                }
                if (!$db->fieldExists($table_user, 'company')) {
                    $db->query("ALTER TABLE `$table_user` ADD `company` VARCHAR(64) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม company</li>';
                }
                if (!$db->fieldExists($table_user, 'phone1')) {
                    $db->query("ALTER TABLE `$table_user` ADD `phone1` VARCHAR(20) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม phone1</li>';
                }
                if (!$db->fieldExists($table_user, 'tax_id')) {
                    $db->query("ALTER TABLE `$table_user` ADD `tax_id` VARCHAR(13) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม tax_id</li>';
                }
                if (!$db->fieldExists($table_user, 'token_expires')) {
                    $db->query("ALTER TABLE `$table_user` ADD `token_expires` DATETIME NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม token_expires</li>';
                }
                if (!$db->fieldExists($table_user, 'visited')) {
                    $db->query("ALTER TABLE `$table_user` ADD `visited` INT(11) NOT NULL DEFAULT 0");
                    $content[] = '<li class="correct">user: เพิ่ม visited</li>';
                }
                if (!$db->fieldExists($table_user, 'website')) {
                    $db->query("ALTER TABLE `$table_user` ADD `website` VARCHAR(255) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">user: เพิ่ม website</li>';
                }

                foreach (['activatecode', 'line_uid', 'telegram_id'] as $_idx) {
                    if (!$db->indexExists($table_user, $_idx)) {
                        $db->query("ALTER TABLE `$table_user` ADD INDEX `$_idx` (`$_idx`)");
                    }
                }
                foreach (['username', 'token', 'id_card', 'phone'] as $_idx) {
                    if (!$db->indexExists($table_user, $_idx)) {
                        $db->query("ALTER TABLE `$table_user` ADD UNIQUE `$_idx` (`$_idx`)");
                    }
                }

                if (!$db->indexExists($table_user, 'idx_status')) {
                    $db->query("ALTER TABLE `$table_user` ADD INDEX `idx_status` (`active`, `status`)");
                    $content[] = '<li class="correct">user: เพิ่ม index idx_status(active, status)</li>';
                }

                $content[] = '<li class="correct">user อัปเกรดสำเร็จ</li>';

                // =========================================================
                // repair
                // =========================================================
                $table_repair = $db_config['prefix'].'_repair';

                if ($db->fieldExists($table_repair, 'create_date')) {
                    $db->query("ALTER TABLE `$table_repair` CHANGE `create_date` `created_at` DATETIME NOT NULL");
                    $content[] = '<li class="correct">repair: เปลี่ยนชื่อ create_date → created_at</li>';
                }

                // =========================================================
                // repair_status
                // =========================================================
                $table_repair_status = $db_config['prefix'].'_repair_status';

                if ($db->fieldExists($table_repair_status, 'create_date')) {
                    $db->query("ALTER TABLE `$table_repair_status` CHANGE `create_date` `created_at` DATETIME NOT NULL");
                    $content[] = '<li class="correct">repair_status: เปลี่ยนชื่อ create_date → created_at</li>';
                }

                // =========================================================
                // category
                // =========================================================
                $table_category = $db_config['prefix'].'_category';

                if ($db->isColumnType($table_category, 'category_id', 'varchar(10)')) {
                    $db->query("UPDATE `$table_category` SET `category_id` = '0' WHERE `category_id` IS NULL");
                    $db->query("ALTER TABLE `$table_category` CHANGE `category_id` `category_id` VARCHAR(10) NOT NULL DEFAULT '0'");
                    $content[] = '<li class="correct">category: แก้ไข category_id เป็น NOT NULL</li>';
                }
                if ($db->isColumnType($table_category, 'language', 'varchar(2)')) {
                    $db->query("UPDATE `$table_category` SET `language` = '' WHERE `language` IS NULL");
                    $db->query("ALTER TABLE `$table_category` CHANGE `language` `language` VARCHAR(2) NOT NULL DEFAULT ''");
                    $content[] = '<li class="correct">category: แก้ไข language เป็น NOT NULL</li>';
                }
                // migrate published → is_active
                if (!$db->fieldExists($table_category, 'is_active')) {
                    $db->query("ALTER TABLE `$table_category` ADD `is_active` TINYINT(1) NULL");
                    if ($db->fieldExists($table_category, 'published')) {
                        $db->query("UPDATE `$table_category` SET `is_active` = `published`");
                    } else {
                        $db->query("UPDATE `$table_category` SET `is_active` = 1");
                    }
                    $db->query("ALTER TABLE `$table_category` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1");
                    $content[] = '<li class="correct">category: เพิ่ม is_active</li>';
                }
                if ($db->fieldExists($table_category, 'published')) {
                    $db->query("ALTER TABLE `$table_category` DROP COLUMN `published`");
                    $content[] = '<li class="correct">category: ลบ published</li>';
                }
                $db->query("UPDATE `$table_category` SET `type` = 'car_accessory' WHERE `type` = 'car_accessories'");
                $content[] = '<li class="correct">category อัปเกรดสำเร็จ</li>';

                // =========================================================
                // logs
                // =========================================================
                $table_logs = $db_config['prefix'].'_logs';

                if ($db->fieldExists($table_logs, 'create_date')) {
                    $db->query("ALTER TABLE `$table_logs` CHANGE `create_date` `created_at` DATETIME NOT NULL");
                    $content[] = '<li class="correct">logs: เปลี่ยนชื่อ create_date → created_at</li>';
                }
                if ($db->isColumnType($table_logs, 'datas', 'text')) {
                    $db->query("ALTER TABLE `$table_logs` CHANGE `datas` `datas` TEXT NULL DEFAULT NULL");
                    $content[] = '<li class="correct">logs: แก้ไข datas เป็น TEXT</li>';
                }
                if ($db->isColumnType($table_logs, 'member_id', 'int(11)')) {
                    $db->query("UPDATE `$table_logs` SET `member_id` = 0 WHERE `member_id` IS NULL");
                    $db->query("ALTER TABLE `$table_logs` CHANGE `member_id` `member_id` INT(11) NOT NULL");
                    $content[] = '<li class="correct">logs: แก้ไข member_id เป็น NOT NULL</li>';
                }
                if ($db->isColumnType($table_logs, 'reason', 'text')) {
                    $db->query("ALTER TABLE `$table_logs` CHANGE `reason` `reason` TEXT NULL DEFAULT NULL");
                    $content[] = '<li class="correct">logs: แก้ไข reason เป็น TEXT</li>';
                }
                if ($db->isColumnType($table_logs, 'topic', 'text')) {
                    $db->query("ALTER TABLE `$table_logs` CHANGE `topic` `topic` TEXT NOT NULL");
                    $content[] = '<li class="correct">logs: แก้ไข topic เป็น TEXT</li>';
                }
                if (!$db->indexExists($table_logs, 'created_at')) {
                    $db->query("ALTER TABLE `$table_logs` ADD INDEX `created_at` (`created_at`)");
                    $content[] = '<li class="correct">logs: เพิ่ม index created_at</li>';
                }
                $content[] = '<li class="correct">logs อัปเกรดสำเร็จ</li>';

                // =========================================================
                // language
                // =========================================================
                $table_language = $db_config['prefix'].'_language';

                foreach (['js', 'la', 'owner'] as $_col) {
                    if ($db->fieldExists($table_language, $_col)) {
                        $db->query("ALTER TABLE `$table_language` DROP COLUMN `$_col`");
                        $content[] = '<li class="correct">language: ลบ '.$_col.'</li>';
                    }
                }
                $content[] = '<li class="correct">language อัปเกรดสำเร็จ</li>';

                // =========================================================
                // inventory / repair / number
                // =========================================================
                $table_inventory = $db_config['prefix'].'_inventory';
                $table_inventory_items = $db_config['prefix'].'_inventory_items';
                $table_inventory_meta = $db_config['prefix'].'_inventory_meta';
                $table_number = $db_config['prefix'].'_number';
                $table_repair = $db_config['prefix'].'_repair';
                $table_repair_status = $db_config['prefix'].'_repair_status';
                $table_assignments = $db_config['prefix'].'_inventory_assignments';

                if (!$db->tableExists($table_assignments)) {
                    $db->query("CREATE TABLE `$table_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_no` varchar(150) NOT NULL,
  `holder_id` int(11) NOT NULL,
  `quantity` float NOT NULL DEFAULT 0,
  `assigned_at` datetime NOT NULL,
  `returned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_no` (`product_no`),
  KEY `holder_id` (`holder_id`),
  KEY `returned_at` (`returned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $content[] = '<li class="correct">inventory_assignments: สร้างตาราง</li>';
                }

                ensureTable($db, $table_inventory, "CREATE TABLE `$table_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` varchar(10) DEFAULT NULL,
  `model_id` varchar(10) DEFAULT NULL,
  `type_id` varchar(10) DEFAULT NULL,
  `topic` varchar(150) NOT NULL,
  `inuse` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `model_id` (`model_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'inventory: สร้างตาราง');
                if ($db->tableExists($table_inventory)) {
                    ensureColumn($db, $table_inventory, 'category_id', "ADD `category_id` VARCHAR(10) NULL DEFAULT NULL", $content, 'inventory: เพิ่ม category_id');
                    ensureColumn($db, $table_inventory, 'model_id', "ADD `model_id` VARCHAR(10) NULL DEFAULT NULL", $content, 'inventory: เพิ่ม model_id');
                    ensureColumn($db, $table_inventory, 'type_id', "ADD `type_id` VARCHAR(10) NULL DEFAULT NULL", $content, 'inventory: เพิ่ม type_id');
                    ensureColumn($db, $table_inventory, 'topic', "ADD `topic` VARCHAR(150) NOT NULL DEFAULT ''", $content, 'inventory: เพิ่ม topic');
                    ensureColumn($db, $table_inventory, 'inuse', "ADD `inuse` TINYINT(1) NULL DEFAULT 1", $content, 'inventory: เพิ่ม inuse');
                    ensureIndex($db, $table_inventory, 'PRIMARY', 'ADD PRIMARY KEY (`id`)', $content, 'inventory: เพิ่ม primary key');
                    ensureIndex($db, $table_inventory, 'category_id', 'ADD KEY `category_id` (`category_id`)', $content, 'inventory: เพิ่ม index category_id');
                    ensureIndex($db, $table_inventory, 'model_id', 'ADD KEY `model_id` (`model_id`)', $content, 'inventory: เพิ่ม index model_id');
                    ensureIndex($db, $table_inventory, 'type_id', 'ADD KEY `type_id` (`type_id`)', $content, 'inventory: เพิ่ม index type_id');
                }

                ensureTable($db, $table_inventory_items, "CREATE TABLE `$table_inventory_items` (
  `product_no` varchar(150) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `stock` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_no`),
  KEY `inventory_id` (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'inventory_items: สร้างตาราง');
                if ($db->tableExists($table_inventory_items)) {
                    ensureColumn($db, $table_inventory_items, 'unit', "ADD `unit` VARCHAR(50) NULL DEFAULT NULL", $content, 'inventory_items: เพิ่ม unit');
                    ensureColumn($db, $table_inventory_items, 'stock', "ADD `stock` FLOAT NOT NULL DEFAULT 0", $content, 'inventory_items: เพิ่ม stock');
                    ensureIndex($db, $table_inventory_items, 'PRIMARY', 'ADD PRIMARY KEY (`product_no`)', $content, 'inventory_items: เพิ่ม primary key');
                    ensureIndex($db, $table_inventory_items, 'inventory_id', 'ADD KEY `inventory_id` (`inventory_id`)', $content, 'inventory_items: เพิ่ม index inventory_id');
                }

                ensureTable($db, $table_inventory_meta, "CREATE TABLE `$table_inventory_meta` (
  `inventory_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` text NOT NULL,
  KEY `inventory_id` (`inventory_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'inventory_meta: สร้างตาราง');
                if ($db->tableExists($table_inventory_meta)) {
                    ensureColumn($db, $table_inventory_meta, 'value', "ADD `value` TEXT NOT NULL", $content, 'inventory_meta: เพิ่ม value');
                    ensureIndex($db, $table_inventory_meta, 'inventory_id', 'ADD KEY `inventory_id` (`inventory_id`)', $content, 'inventory_meta: เพิ่ม index inventory_id');
                    ensureIndex($db, $table_inventory_meta, 'name', 'ADD KEY `name` (`name`)', $content, 'inventory_meta: เพิ่ม index name');
                }

                ensureTable($db, $table_number, "CREATE TABLE `$table_number` (
  `type` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `auto_increment` int(11) NOT NULL,
  `updated_at` date DEFAULT NULL,
  PRIMARY KEY (`type`,`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'number: สร้างตาราง');
                if ($db->tableExists($table_number)) {
                    if ($db->fieldExists($table_number, 'last_update') && !$db->fieldExists($table_number, 'updated_at')) {
                        $db->query("ALTER TABLE `$table_number` CHANGE `last_update` `updated_at` DATE NULL DEFAULT NULL");
                        $content[] = '<li class="correct">number: เปลี่ยนชื่อ last_update → updated_at</li>';
                    } elseif (!$db->fieldExists($table_number, 'updated_at')) {
                        ensureColumn($db, $table_number, 'updated_at', "ADD `updated_at` DATE NULL DEFAULT NULL", $content, 'number: เพิ่ม updated_at');
                    }
                    ensureIndex($db, $table_number, 'PRIMARY', 'ADD PRIMARY KEY (`type`,`prefix`)', $content, 'number: เพิ่ม primary key');
                }

                ensureTable($db, $table_repair, "CREATE TABLE `$table_repair` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_no` varchar(150) NOT NULL,
  `job_id` varchar(20) NOT NULL,
  `job_description` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `repair_no` varchar(50) DEFAULT NULL,
  `informer` varchar(150) DEFAULT NULL,
  `appraiser` float DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id` (`job_id`),
  KEY `product_no` (`product_no`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'repair: สร้างตาราง');
                if ($db->tableExists($table_repair)) {
                    ensureColumn($db, $table_repair, 'appointment_date', "ADD `appointment_date` DATE NULL DEFAULT NULL", $content, 'repair: เพิ่ม appointment_date');
                    ensureColumn($db, $table_repair, 'repair_no', "ADD `repair_no` VARCHAR(50) NULL DEFAULT NULL", $content, 'repair: เพิ่ม repair_no');
                    ensureColumn($db, $table_repair, 'informer', "ADD `informer` VARCHAR(150) NULL DEFAULT NULL", $content, 'repair: เพิ่ม informer');
                    ensureColumn($db, $table_repair, 'appraiser', "ADD `appraiser` FLOAT NULL DEFAULT 0", $content, 'repair: เพิ่ม appraiser');
                    ensureIndex($db, $table_repair, 'PRIMARY', 'ADD PRIMARY KEY (`id`)', $content, 'repair: เพิ่ม primary key');
                    ensureIndex($db, $table_repair, 'job_id', 'ADD UNIQUE KEY `job_id` (`job_id`)', $content, 'repair: เพิ่ม unique job_id');
                    ensureIndex($db, $table_repair, 'product_no', 'ADD KEY `product_no` (`product_no`)', $content, 'repair: เพิ่ม index product_no');
                    ensureIndex($db, $table_repair, 'customer_id', 'ADD KEY `customer_id` (`customer_id`)', $content, 'repair: เพิ่ม index customer_id');
                }

                ensureTable($db, $table_repair_status, "CREATE TABLE `$table_repair_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `comment` varchar(1000) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `cost` float DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `repair_id` (`repair_id`),
  KEY `operator_id` (`operator_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $content, 'repair_status: สร้างตาราง');
                if ($db->tableExists($table_repair_status)) {
                    ensureColumn($db, $table_repair_status, 'cost', "ADD `cost` FLOAT NULL DEFAULT 0", $content, 'repair_status: เพิ่ม cost');
                    ensureIndex($db, $table_repair_status, 'PRIMARY', 'ADD PRIMARY KEY (`id`)', $content, 'repair_status: เพิ่ม primary key');
                    ensureIndex($db, $table_repair_status, 'repair_id', 'ADD KEY `repair_id` (`repair_id`)', $content, 'repair_status: เพิ่ม index repair_id');
                    ensureIndex($db, $table_repair_status, 'operator_id', 'ADD KEY `operator_id` (`operator_id`)', $content, 'repair_status: เพิ่ม index operator_id');
                    ensureIndex($db, $table_repair_status, 'member_id', 'ADD KEY `member_id` (`member_id`)', $content, 'repair_status: เพิ่ม index member_id');
                }

                ensureCategory($db, $table_category, 'category_id', '1', 'เครื่องใช้ไฟฟ้า', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น category_id/1');
                ensureCategory($db, $table_category, 'category_id', '2', 'วัสดุสำนักงาน', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น category_id/2');
                ensureCategory($db, $table_category, 'type_id', '1', 'เครื่องคอมพิวเตอร์', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น type_id/1');
                ensureCategory($db, $table_category, 'type_id', '2', 'เครื่องพิมพ์', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น type_id/2');
                ensureCategory($db, $table_category, 'model_id', '1', 'Apple', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น model_id/1');
                ensureCategory($db, $table_category, 'model_id', '2', 'Asus', null, 1, $content, 'category: เพิ่มหมวดหมู่เริ่มต้น model_id/2');
                ensureCategory($db, $table_category, 'unit', 'เครื่อง', 'เครื่อง', null, 1, $content, 'category: เพิ่มหน่วยนับเริ่มต้น unit/เครื่อง');
                ensureCategory($db, $table_category, 'unit', 'อัน', 'อัน', null, 1, $content, 'category: เพิ่มหน่วยนับเริ่มต้น unit/อัน');
                ensureCategory($db, $table_category, 'repairstatus', '1', 'แจ้งซ่อม', '#660000', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/1');
                ensureCategory($db, $table_category, 'repairstatus', '2', 'กำลังดำเนินการ', '#120eeb', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/2');
                ensureCategory($db, $table_category, 'repairstatus', '3', 'รออะไหล่', '#d940ff', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/3');
                ensureCategory($db, $table_category, 'repairstatus', '4', 'ซ่อมสำเร็จ', '#06d628', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/4');
                ensureCategory($db, $table_category, 'repairstatus', '5', 'ซ่อมไม่สำเร็จ', '#FF0000', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/5');
                ensureCategory($db, $table_category, 'repairstatus', '6', 'ยกเลิกการซ่อม', '#FF6F00', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/6');
                ensureCategory($db, $table_category, 'repairstatus', '7', 'ส่งมอบเรียบร้อย', '#000000', 1, $content, 'category: เพิ่มสถานะซ่อม repairstatus/7');
                $content[] = '<li class="correct">inventory/repair อัปเกรดสำเร็จ</li>';

                // บันทึก settings/config.php
                $config['version'] = $new_config['version'];
                $config['reversion'] = time();
                ensureConfigDefault($config, 'inventory_w', 800, $content, 'config: เพิ่ม inventory_w');
                ensureConfigDefault($config, 'inventory_warranty_alert_days', 30, $content, 'config: เพิ่ม inventory_warranty_alert_days');
                ensureConfigDefault($config, 'repair_first_status', 1, $content, 'config: เพิ่ม repair_first_status');
                ensureConfigDefault($config, 'repair_prefix', '', $content, 'config: เพิ่ม repair_prefix');
                ensureConfigDefault($config, 'repair_job_no', 'JOB%04d', $content, 'config: เพิ่ม repair_job_no');
                if (function_exists('imagewebp')) {
                    $config['stored_img_type'] = isset($config['stored_img_type']) ? $config['stored_img_type'] : '.jpg';
                } else {
                    $config['stored_img_type'] = '.jpg';
                }
                if (isset($new_config['default_icon'])) {
                    $config['default_icon'] = $new_config['default_icon'];
                }
                // กำหนดค่า API หากยังไม่มี
                include_once ROOT_PATH.'Kotchasan/Password.php';
                if (empty($config['api_tokens']['internal']) || empty($config['api_tokens']['external'])) {
                    $config['api_tokens'] = [
                        'internal' => \Kotchasan\Password::uniqid(40),
                        'external' => \Kotchasan\Password::uniqid(40)
                    ];
                }
                if (empty($config['api_secret'])) {
                    $config['api_secret'] = \Kotchasan\Password::uniqid();
                }
                if (empty($config['jwt_secret'])) {
                    $config['jwt_secret'] = \Kotchasan\Password::uniqid(64);
                }
                if (!isset($config['api_ips'])) {
                    $config['api_ips'] = ['0.0.0.0'];
                }
                if (!isset($config['api_cors'])) {
                    $config['api_cors'] = '*';
                }
                $f = save($config, ROOT_PATH.'settings/config.php');
                $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">บันทึก <b>config.php</b> ...</li>';
                // นำเข้าภาษา
                include ROOT_PATH.'install/language.php';
            } catch (\PDOException $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            } catch (\Exception $exc) {
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
                $error = true;
            }
            if (!$error) {
                echo '<h2>ปรับรุ่นเรียบร้อย</h2>';
                echo '<p>การปรับรุ่นได้ดำเนินการเสร็จเรียบร้อยแล้ว หากคุณต้องการความช่วยเหลือในการใช้งาน คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class=warning>กรุณาลบไดเร็คทอรี่ <em>install/</em> ออกจาก Server ของคุณ</p>';
                echo '<p>คุณควรปรับ chmod ให้ไดเร็คทอรี่ <em>datas/</em> และ <em>settings/</em> (และไดเร็คทอรี่อื่นๆที่คุณได้ปรับ chmod ไว้ก่อนการปรับรุ่น) ให้เป็น 644 ก่อนดำเนินการต่อ (ถ้าคุณได้ทำการปรับ chmod ไว้ด้วยตัวเอง)</p>';
                echo '<p class="submit"><a href="../" class="btn btn-primary large">เข้าระบบ</a></p>';
            } else {
                echo '<h2>ปรับรุ่นไม่สำเร็จ</h2>';
                echo '<p>การปรับรุ่นยังไม่สมบูรณ์ ลองตรวจสอบข้อผิดพลาดที่เกิดขึ้นและแก้ไขดู หากคุณต้องการความช่วยเหลือการติดตั้ง คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class="submit"><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
            }
        }
    }
}

/**
 * @param Db $db
 * @param string $table_name
 * @param string $username
 * @param string $password
 * @param string $password_key
 */
function updateAdmin($db, $table_name, $username, $password, $password_key)
{
    include ROOT_PATH.'Kotchasan/Text.php';
    $username = \Kotchasan\Text::username($username);
    $password = \Kotchasan\Text::password($password);
    $result = $db->first($table_name, [
        'username' => $username,
        'status' => 1
    ]);
    if (!$result || $result->id > 1) {
        throw new \Exception('ชื่อผู้ใช้ไม่ถูกต้อง หรือไม่ใช่ผู้ดูแลระบบสูงสุด');
    } elseif ($result->password === sha1($password.$result->salt)) {
        // password เวอร์ชั่นเก่า
        $password = sha1($password_key.$password.$result->salt);
        $db->update($table_name, ['id' => $result->id], ['password' => $password]);
    } elseif ($result->password != sha1($password_key.$password.$result->salt)) {
        throw new \Exception('รหัสผ่านไม่ถูกต้อง');
    }
}

/**
 * @param array $config
 * @param string $file
 */
function save($config, $file)
{
    $f = @fopen($file, 'wb');
    if ($f !== false) {
        if (!preg_match('/^.*\/([^\/]+)\.php?/', $file, $match)) {
            $match[1] = 'config';
        }
        fwrite($f, '<'."?php\n/* $match[1].php */\nreturn ".var_export((array) $config, true).';');
        fclose($f);
        return true;
    } else {
        return false;
    }
}

/**
 * Ensure a table exists.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $sql
 * @param array $content
 * @param string $message
 */
function ensureTable($db, $table_name, $sql, &$content, $message)
{
    if (!$db->tableExists($table_name)) {
        $db->query($sql);
        $content[] = '<li class="correct">'.$message.'</li>';
    }
}

/**
 * Ensure a column exists.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $field
 * @param string $sql
 * @param array $content
 * @param string $message
 */
function ensureColumn($db, $table_name, $field, $sql, &$content, $message)
{
    if (!$db->fieldExists($table_name, $field)) {
        $db->query("ALTER TABLE `$table_name` $sql");
        $content[] = '<li class="correct">'.$message.'</li>';
    }
}

/**
 * Ensure an index exists.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $index
 * @param string $sql
 * @param array $content
 * @param string $message
 */
function ensureIndex($db, $table_name, $index, $sql, &$content, $message)
{
    if (!$db->indexExists($table_name, $index)) {
        $db->query("ALTER TABLE `$table_name` $sql");
        $content[] = '<li class="correct">'.$message.'</li>';
    }
}

/**
 * Ensure a category row exists.
 *
 * @param Db $db
 * @param string $table_name
 * @param string $type
 * @param string $category_id
 * @param string $topic
 * @param string|null $color
 * @param int $is_active
 * @param array $content
 * @param string $message
 */
function ensureCategory($db, $table_name, $type, $category_id, $topic, $color, $is_active, &$content, $message)
{
    $result = $db->first($table_name, [
        'type' => $type,
        'category_id' => $category_id,
        'language' => ''
    ]);
    if (!$result) {
        $db->insert($table_name, [
            'type' => $type,
            'category_id' => $category_id,
            'language' => '',
            'topic' => $topic,
            'color' => $color,
            'is_active' => $is_active
        ]);
        $content[] = '<li class="correct">'.$message.'</li>';
    }
}

/**
 * Ensure a config key exists.
 *
 * @param array $config
 * @param string $key
 * @param mixed $value
 * @param array $content
 * @param string $message
 */
function ensureConfigDefault(&$config, $key, $value, &$content, $message)
{
    if (!isset($config[$key])) {
        $config[$key] = $value;
        $content[] = '<li class="correct">'.$message.'</li>';
    }
}
