<?php
/**
 * @filesource modules/repair/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Init;

use Gcms\Api as ApiController;

/**
 * ลงทะเบียนเมนูและสิทธิ์ของโมดูลแจ้งซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สิทธิ์ของโมดูล
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = [
            'value' => 'can_manage_repair',
            'text' => '{LNG_Can manage repair}'
        ];
        $permissions[] = [
            'value' => 'can_repair',
            'text' => '{LNG_Repairman}'
        ];

        return $permissions;
    }

    /**
     * เมนูของโมดูล
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        // เมนูของสมาชิกทุกคน
        $memberMenu = [
            [
                'title' => '{LNG_Get a repair}',
                'url' => '/repair-receive',
                'icon' => 'icon-tools'
            ],
            [
                'title' => '{LNG_Repair history}',
                'url' => '/repair-history',
                'icon' => 'icon-list'
            ]
        ];

        // เจ้าหน้าที่และช่างซ่อม
        if (ApiController::hasPermission($login, ['can_manage_repair', 'can_repair'])) {
            $memberMenu[] = [
                'title' => '{LNG_Repair list}',
                'url' => '/repair-jobs',
                'icon' => 'icon-file'
            ];
        }

        $menus = parent::insertMenuAfter($menus, $memberMenu, 0);

        if (!ApiController::hasPermission($login, 'can_config') || !isset($menus['settings'])) {
            return $menus;
        }

        $settingsMenu = [
            [
                'title' => '{LNG_Repair}',
                'icon' => 'icon-tools',
                'children' => [
                    [
                        'title' => '{LNG_Module Settings}',
                        'url' => '/repair-settings',
                        'icon' => 'icon-cog'
                    ],
                    [
                        'title' => '{LNG_Repair status}',
                        'url' => '/repair-statuses',
                        'icon' => 'icon-star0'
                    ]
                ]
            ]
        ];

        return parent::insertMenuChildren($menus, $settingsMenu, 'settings', null, 1);
    }
}
