<?php
/**
 * @filesource modules/repair/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        // เมนูซ่อม เห็นทุกคน
        $submenus = array(
            array(
                'text' => '{LNG_Get a repair}',
                'url' => 'index.php?module=repair-receive'
            ),
            array(
                'text' => '{LNG_Repair history}',
                'url' => 'index.php?module=repair-history'
            )
        );
        // สามารถจัดการรายการซ่อมได้, ช่างซ่อม
        if (Login::checkPermission($login, array('can_manage_repair', 'can_repair'))) {
            $submenus[] = array(
                'text' => '{LNG_Repair list} ({LNG_Repairman})',
                'url' => 'index.php?module=repair-setup'
            );
        }
        // เมนูแจ้งซ่อม
        $menu->add('repair', '{LNG_Repair jobs}', null, $submenus);
        $menu->addTopLvlMenu('repair', '{LNG_Repair jobs}', null, $submenus, 'member');
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission($login, 'can_config')) {
            $submenus = array(
                array(
                    'text' => '{LNG_Module settings}',
                    'url' => 'index.php?module=repair-settings'
                ),
                array(
                    'text' => '{LNG_Repair status}',
                    'url' => 'index.php?module=repair-repairstatus'
                )
            );
            $menu->add('settings', '{LNG_Repair}', null, $submenus, 'repair');
        }
    }
}
