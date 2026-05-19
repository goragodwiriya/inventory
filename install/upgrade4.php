<?php
if (defined('ROOT_PATH')) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        include ROOT_PATH.'install/upgrade3.php';
    } else {
        $error = false;
        include ROOT_PATH.'install/db.php';
        $db_config = include ROOT_PATH.'settings/database.php';
        try {
            $db_config = $db_config['mysql'];
            $db = new Db($db_config);
        } catch (\Exception $exc) {
            $error = true;
            echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
            echo '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้</p>';
        }
        if (!$error) {
            $content = ['<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>'];
            try {
                $table_items = $db_config['prefix'].'_inventory_items';
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

                $itemProductNo = $db->customQuery("SHOW FULL COLUMNS FROM `$table_items` LIKE 'product_no'");
                if (!empty($itemProductNo) && !empty($itemProductNo[0]->Collation)) {
                    $db->query(
                        "ALTER TABLE `$table_assignments` MODIFY `product_no` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE ".$itemProductNo[0]->Collation." NOT NULL"
                    );
                }

                if ($db->fieldExists($table_items, 'holder_id')) {
                    $rows = $db->customQuery("SELECT `product_no`, `holder_id`, `stock` FROM `$table_items` WHERE `holder_id` IS NOT NULL");
                    $migrated = 0;
                    if (is_iterable($rows)) {
                        foreach ($rows as $row) {
                            $db->query(
                                "INSERT INTO `$table_assignments` (`product_no`, `holder_id`, `quantity`, `assigned_at`, `returned_at`) VALUES ('".
                                addslashes($row->product_no)."', ".(int) $row->holder_id.", ".(float) $row->stock.", NOW(), NULL)"
                            );
                            ++$migrated;
                        }
                    }

                    $db->query("ALTER TABLE `$table_items` DROP `holder_id`");
                    $content[] = '<li class="correct">inventory_items: ลบ holder_id และย้ายข้อมูล '.$migrated.' รายการไปยัง inventory_assignments</li>';
                }

                $content[] = '<li class="correct">inventory holder assignments อัปเกรดสำเร็จ</li>';
            } catch (\Exception $exc) {
                $error = true;
                $content[] = '<li class="incorrect">'.$exc->getMessage().'</li>';
            }

            if (!$error) {
                echo '<h2>ปรับรุ่นเรียบร้อย</h2>';
                echo '<p>การปรับรุ่นได้ดำเนินการเสร็จเรียบร้อยแล้ว</p>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class=warning>กรุณาลบไดเร็คทอรี่ <em>install/</em> ออกจาก Server ของคุณ</p>';
                echo '<p class="submit"><a href="../" class="btn btn-primary large">เข้าระบบ</a></p>';
            } else {
                echo '<h2>ปรับรุ่นไม่สำเร็จ</h2>';
                echo '<ul>'.implode('', $content).'</ul>';
                echo '<p class="submit"><a href="." class="btn btn-primary large">ลองใหม่</a></p>';
            }
        }
    }
}