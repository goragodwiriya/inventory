<?php
if (defined('ROOT_PATH')) {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        include ROOT_PATH.'install/upgrade2.php';
    } else {
        $error = false;
        // Database Class
        include ROOT_PATH.'install/db.php';
        // ค่าติดตั้งฐานข้อมูล
        $db_config = include ROOT_PATH.'settings/database.php';
        try {
            $db_config = $db_config['mysql'];
            // เชื่อมต่อฐานข้อมูล
            $db = new Db($db_config);
        } catch (\Exception $exc) {
            $error = true;
            echo '<h2>ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล</h2>';
            echo '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้</p>';
        }
        if (!$error) {
            $content = ['<li class="correct">เชื่อมต่อฐานข้อมูลสำเร็จ</li>'];
            try {
                // =========================================================
                // inventory_items: เพิ่ม holder_id
                // =========================================================
                $table_items = $db_config['prefix'].'_inventory_items';

                if (!$db->fieldExists($table_items, 'holder_id')) {
                    $db->query("ALTER TABLE `$table_items` ADD `holder_id` INT(11) NULL DEFAULT NULL");
                    $content[] = '<li class="correct">inventory_items: เพิ่ม holder_id</li>';
                }

                // =========================================================
                // ย้ายข้อมูล holder_id จาก inventory_meta → inventory_items
                // (กรณีติดตั้งเวอร์ชันเก่าที่เก็บ holder_id ใน meta)
                // =========================================================
                $table_meta = $db_config['prefix'].'_inventory_meta';

                $metaRows = $db->query(
                    "SELECT `inventory_id`, `value` FROM `$table_meta` WHERE `name` = 'holder_id' AND `value` != ''"
                );
                $migrated = 0;
                if ($metaRows) {
                    foreach ($metaRows as $row) {
                        $db->query(
                            "UPDATE `$table_items` SET `holder_id` = ".(int) $row->value.
                            " WHERE `inventory_id` = ".(int) $row->inventory_id.
                            " AND `holder_id` IS NULL"
                        );
                        ++$migrated;
                    }
                }
                if ($migrated > 0) {
                    $db->query("DELETE FROM `$table_meta` WHERE `name` = 'holder_id'");
                    $content[] = '<li class="correct">inventory_meta: ย้าย holder_id ('.$migrated.' รายการ) ไปยัง inventory_items</li>';
                }

                $content[] = '<li class="correct">inventory_items อัปเกรดสำเร็จ</li>';
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
