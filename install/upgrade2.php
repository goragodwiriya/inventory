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
            echo '<p><a href="index.php?step=1" class="button large pink">กลับไปลองใหม่</a></p>';
        }
        if (!$error) {
            // เชื่อมต่อฐานข้อมูลสำเร็จ
            $content = array('<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>');
            try {
                // ตาราง user
                $table_user = $db_config['prefix'].'_user';
                if (empty($config['password_key'])) {
                    // อัปเดตข้อมูลผู้ดูแลระบบ
                    $config['password_key'] = uniqid();
                }
                // ตรวจสอบการ login
                updateAdmin($db, $table_user, $_POST['username'], $_POST['password'], $config['password_key']);
                if (!$db->fieldExists($table_user, 'social')) {
                    $db->query("ALTER TABLE `$table_user` CHANGE `fb` `social` TINYINT(1) NOT NULL DEFAULT 0");
                }
                if (!$db->fieldExists($table_user, 'country')) {
                    $db->query("ALTER TABLE `$table_user` ADD `country` VARCHAR(2)");
                }
                if (!$db->fieldExists($table_user, 'province')) {
                    $db->query("ALTER TABLE `$table_user` ADD `province` VARCHAR(50)");
                }
                if (!$db->fieldExists($table_user, 'token')) {
                    $db->query("ALTER TABLE `$table_user` ADD `token` VARCHAR(50) NULL AFTER `password`");
                }
                $db->query("ALTER TABLE `$table_user` CHANGE `address` `address` VARCHAR(150) DEFAULT NULL");
                $db->query("ALTER TABLE `$table_user` CHANGE `password` `password` VARCHAR(50) NOT NULL");
                $db->query("ALTER TABLE `$table_user` CHANGE `username` `username` VARCHAR(50) DEFAULT NULL");
                if ($db->fieldExists($table_user, 'visited')) {
                    $db->query("ALTER TABLE `$table_user` DROP `visited`");
                }
                if ($db->fieldExists($table_user, 'lastvisited')) {
                    $db->query("ALTER TABLE `$table_user` DROP `lastvisited`");
                }
                if ($db->fieldExists($table_user, 'session_id')) {
                    $db->query("ALTER TABLE `$table_user` DROP `session_id`");
                }
                if ($db->fieldExists($table_user, 'ip')) {
                    $db->query("ALTER TABLE `$table_user` DROP `ip`");
                }
                if (!$db->indexExists($table_user, 'phone')) {
                    $db->query("ALTER TABLE `$table_user` ADD INDEX (`phone`)");
                }
                if (!$db->indexExists($table_user, 'id_card')) {
                    $db->query("ALTER TABLE `$table_user` ADD INDEX (`id_card`)");
                }
                if (!$db->indexExists($table_user, 'token')) {
                    $db->query("ALTER TABLE `$table_user` ADD INDEX (`token`)");
                }
                if (!$db->fieldExists($table_user, 'line_uid')) {
                    $db->query("ALTER TABLE `$table_user` ADD `line_uid` VARCHAR(33) DEFAULT NULL");
                }
                if (!$db->indexExists($table_user, 'line_uid')) {
                    $db->query("ALTER TABLE `$table_user` ADD INDEX (`line_uid`)");
                }
                if (!$db->fieldExists($table_user, 'activatecode')) {
                    $db->query("ALTER TABLE `$table_user` ADD `activatecode` VARCHAR(32) NOT NULL DEFAULT '', ADD INDEX (`activatecode`)");
                }
                $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_user.'` สำเร็จ</li>';
                $table_logs = $db_config['prefix'].'_logs';
                if (!$db->tableExists($table_logs)) {
                    $sql = 'CREATE TABLE `'.$table_logs.'` (';
                    $sql .= ' `id` int(11) NOT NULL,';
                    $sql .= ' `src_id` int(11) NOT NULL,';
                    $sql .= ' `module` varchar(20) COLLATE utf8_unicode_ci NOT NULL,';
                    $sql .= ' `action` varchar(20) COLLATE utf8_unicode_ci NOT NULL,';
                    $sql .= ' `create_date` datetime NOT NULL,';
                    $sql .= ' `reason` text COLLATE utf8_unicode_ci DEFAULT NULL,';
                    $sql .= ' `member_id` int(11) DEFAULT NULL,';
                    $sql .= ' `topic` text COLLATE utf8_unicode_ci NOT NULL,';
                    $sql .= ' `datas` text COLLATE utf8_unicode_ci DEFAULT NULL';
                    $sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
                    $db->query($sql);
                    $sql = 'ALTER TABLE `'.$table_logs.'`';
                    $sql .= ' ADD PRIMARY KEY (`id`),';
                    $sql .= ' ADD KEY `src_id` (`src_id`),';
                    $sql .= ' ADD KEY `module` (`module`),';
                    $sql .= ' ADD KEY `action` (`action`);';
                    $db->query($sql);
                    $sql = 'ALTER TABLE `'.$table_logs.'` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;';
                    $db->query($sql);
                    $content[] = '<li class="correct">สร้างตาราง `'.$table_logs.'` สำเร็จ</li>';
                }
                // ตาราง category
                $table_category = $db_config['prefix'].'_category';
                $table_user_meta = $db_config['prefix'].'_user_meta';
                if (!$db->tableExists($table_user_meta)) {
                    $sql = 'CREATE TABLE `'.$table_user_meta.'` (';
                    $sql .= ' `value` varchar(10) NOT NULL,';
                    $sql .= ' `name` varchar(10) DEFAULT NULL,';
                    $sql .= ' `member_id` int(11) NOT NULL';
                    $sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
                    $db->query($sql);
                    $sql = 'ALTER TABLE `'.$table_user_meta.'` ADD KEY `member_id` (`member_id`,`name`);';
                    $db->query($sql);
                    $content[] = '<li class="correct">สร้างตาราง `'.$table_user_meta.'` สำเร็จ</li>';
                }
                // ตาราง inventory, inventory_items,stock
                $table_inventory = $db_config['prefix'].'_inventory';
                $table_items = $db_config['prefix'].'_inventory_items';
                $table_meta = $db_config['prefix'].'_inventory_meta';
                $table_stock = $db_config['prefix'].'_inventory_stock';
                if (!$db->tableExists($table_items) && $db->tableExists($table_stock)) {
                    $db->query("CREATE TABLE `$table_items` LIKE `$table_stock`");
                    $db->query("INSERT `$table_items` SELECT * FROM `$table_stock`");
                    $db->query("ALTER TABLE `$table_items` ADD `unit` VARCHAR(50) DEFAULT NULL");
                    $db->query("UPDATE `$table_items` AS I SET `unit`=(SELECT C.`topic` FROM `$table_inventory` AS V INNER JOIN `$table_category` AS C ON C.`category_id`=V.`unit` AND C.`type`='unit' WHERE V.`id`=I.`inventory_id`)");
                    $db->query("ALTER TABLE `$table_items` DROP `create_date`, DROP `id`");
                    $db->query("ALTER TABLE `$table_inventory` DROP `unit`");
                    $db->query("ALTER TABLE `$table_items` DROP INDEX `product_no`, ADD PRIMARY KEY (`product_no`)");
                    $db->query("DROP TABLE `$table_stock`");
                    $content[] = '<li class="correct">สร้างตาราง `'.$table_items.'` สำเร็จ</li>';
                }
                // ตาราง inventory
                if ($db->fieldExists($table_inventory, 'equipment')) {
                    $db->query("ALTER TABLE `$table_inventory` CHANGE `equipment` `topic` VARCHAR(150) DEFAULT NULL");
                }
                if ($db->fieldExists($table_inventory, 'serial')) {
                    $db->query("ALTER TABLE `$table_inventory` CHANGE `serial` `product_no` VARCHAR(150) DEFAULT NULL");
                }
                // ตาราง inventory
                if (!$db->fieldExists($table_inventory, 'category_id')) {
                    $db->query("ALTER TABLE `$table_inventory` ADD `category_id` INT, ADD INDEX (`category_id`)");
                }
                if (!$db->fieldExists($table_inventory, 'model_id')) {
                    $db->query("ALTER TABLE `$table_inventory` ADD `model_id` INT, ADD INDEX (`model_id`)");
                }
                if (!$db->fieldExists($table_inventory, 'type_id')) {
                    $db->query("ALTER TABLE `$table_inventory` ADD `type_id` INT, ADD INDEX (`type_id`)");
                }
                if (!$db->fieldExists($table_inventory, 'inuse')) {
                    if ($db->fieldExists($table_inventory, 'status')) {
                        $db->query("ALTER TABLE `$table_inventory` CHANGE `status` `inuse` TINYINT(1) DEFAULT 1");
                    } else {
                        $db->query("ALTER TABLE `$table_inventory` ADD `inuse` TINYINT(1) DEFAULT 1");
                    }
                }
                if ($db->fieldExists($table_inventory, 'create_date')) {
                    $db->query("ALTER TABLE `$table_inventory` DROP `create_date`");
                }
                if (!$db->fieldExists($table_inventory, 'count_stock')) {
                    $db->query("ALTER TABLE `$table_inventory` ADD `count_stock` TINYINT(1) DEFAULT 1");
                }
                if ($db->fieldExists($table_inventory, 'detail') && $db->tableExists($table_meta)) {
                    $db->query("UPDATE `$table_inventory` SET `category_id`=(SELECT `value` FROM `$table_meta` WHERE `inventory_id`=`id` AND `name`='category_id')");
                    $db->query("UPDATE `$table_inventory` SET `model_id`=(SELECT `value` FROM `$table_meta` WHERE `inventory_id`=`id` AND `name`='model_id')");
                    $db->query("UPDATE `$table_inventory` SET `type_id`=(SELECT `value` FROM `$table_meta` WHERE `inventory_id`=`id` AND `name`='type_id')");
                    $db->query("DELETE FROM `$table_meta` WHERE `name` IN ('category_id', 'model_id', 'type_id', 'unit')");
                }
                if ($db->tableExists($table_meta)) {
                    $db->query("ALTER TABLE `$table_meta` CHANGE `value` `value` TEXT NOT NULL");
                } else {
                    $db->query("CREATE TABLE `$table_meta` (`inventory_id` int(11) NOT NULL,`name` VARCHAR(20) NOT NULL,`value` TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
                    $db->query("ALTER TABLE `$table_meta` ADD KEY `inventory_id` (`inventory_id`)");
                }
                if ($db->fieldExists($table_inventory, 'detail')) {
                    $db->query("INSERT INTO `$table_meta` (`inventory_id`,`name`,`value`) SELECT `id`,'detail',`detail` FROM `$table_inventory` WHERE `detail`!=''");
                    $db->query("ALTER TABLE `$table_inventory` DROP `detail`");
                }
                $db->query("ALTER TABLE `$table_inventory` CHANGE `category_id` `category_id` VARCHAR(10) NOT NULL DEFAULT '0'");
                $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_meta.'` สำเร็จ</li>';
                $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_inventory.'` สำเร็จ</li>';
                // ตาราง repair
                $table_repair = $db_config['prefix'].'_repair';
                if (!$db->fieldExists($table_repair, 'product_no')) {
                    $db->query("ALTER TABLE `$table_repair` ADD `product_no` VARCHAR(50) NOT NULL");
                    $db->query("UPDATE `$table_repair` AS R SET `product_no`=(SELECT `product_no` FROM `$table_items` AS I WHERE I.`inventory_id`=R.`inventory_id`)");
                    $db->query("ALTER TABLE `$table_repair` DROP `inventory_id`");
                }
                $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_repair.'` สำเร็จ</li>';
                // category
                $db->query("DELETE FROM `$table_category` WHERE `type` IN ('unit')");
                $db->query("ALTER TABLE `$table_category` CHANGE `category_id` `category_id` VARCHAR(10) NOT NULL DEFAULT '0'");
                $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_category.'` สำเร็จ</li>';
                // ตาราง number
                $table_number = $db_config['prefix'].'_number';
                if (!$db->tableExists($table_number)) {
                    $sql = 'CREATE TABLE `'.$table_number.'` (';
                    $sql .= ' `type` varchar(20) NOT NULL,';
                    $sql .= ' `prefix` varchar(20) NOT NULL,';
                    $sql .= ' `auto_increment` int(11) NOT NULL,';
                    $sql .= ' `last_update` date DEFAULT NULL';
                    $sql .= ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
                    $db->query($sql);
                    $sql = 'ALTER TABLE `'.$table_number.'` ADD PRIMARY KEY (`type`,`prefix`)';
                    $db->query($sql);
                    $content[] = '<li class="correct">สร้างตาราง `'.$table_number.'` สำเร็จ</li>';
                } elseif (!$db->fieldExists($table_number, 'prefix')) {
                    $db->query("ALTER TABLE `$table_number` DROP PRIMARY KEY");
                    $db->query("ALTER TABLE `$table_number` ADD `prefix` VARCHAR(20) NOT NULL AFTER `type`");
                    $db->query("ALTER TABLE `$table_number` ADD PRIMARY KEY (`type`, `prefix`)");
                    $content[] = '<li class="correct">ปรับปรุงตาราง `'.$table_number.'` สำเร็จ</li>';
                }
                // บันทึก settings/config.php
                $config['version'] = $new_config['version'];
                $config['reversion'] = time();
                if (isset($new_config['default_icon'])) {
                    $config['default_icon'] = $new_config['default_icon'];
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
                echo '<p><a href="../index.php" class="button large admin">เข้าระบบ</a></p>';
            } else {
                echo '<h2>ปรับรุ่นไม่สำเร็จ</h2>';
                echo '<p>การปรับรุ่นยังไม่สมบูรณ์ ลองตรวจสอบข้อผิดพลาดที่เกิดขึ้นและแก้ไขดู หากคุณต้องการความช่วยเหลือการติดตั้ง คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://www.kotchasan.com" target="_blank">https://www.kotchasan.com</a></p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p><a href="." class="button large admin">ลองใหม่</a></p>';
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
    $result = $db->first($table_name, array(
        'username' => $username,
        'status' => 1
    ));
    if (!$result || $result->id > 1) {
        throw new \Exception('ชื่อผู้ใช้ไม่ถูกต้อง หรือไม่ใช่ผู้ดูแลระบบสูงสุด');
    } elseif ($result->password === sha1($password.$result->salt)) {
        // password เวอร์ชั่นเก่า
        $password = sha1($password_key.$password.$result->salt);
        $db->update($table_name, array('id' => $result->id), array('password' => $password));
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
