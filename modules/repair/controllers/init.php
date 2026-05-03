<?php
/**
 * @filesource modules/repair/controllers/init.php
 */

namespace Repair\Init;

use Gcms\Api as ApiController;

class Controller extends \Gcms\Controller
{
    /**
     * Register repair permissions.
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
            'text' => '{LNG_Can manage} {LNG_Repair}'
        ];
        $permissions[] = [
            'value' => 'can_repair',
            'text' => '{LNG_Repairman}'
        ];

        return $permissions;
    }

    /**
     * Register repair menus.
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

        $memberMenu = [
            [
                'title' => '{LNG_Get a repair}',
                'url' => '/repair-request',
                'icon' => 'icon-write'
            ],
            [
                'title' => '{LNG_Repair history}',
                'url' => '/repair-history',
                'icon' => 'icon-list'
            ]
        ];
        if (\Repair\Helper\Controller::canProcessRepair($login)) {
            $memberMenu[] = [
                'title' => '{LNG_Repair jobs}',
                'url' => '/repair-jobs',
                'icon' => 'icon-tools'
            ];
        }
        $menus = parent::insertMenuAfter($menus, $memberMenu, 0);

        if (!ApiController::hasPermission($login, ['can_manage_repair', 'can_config'])) {
            return $menus;
        }

        $settingsMenu = [
            [
                'title' => '{LNG_Repair}',
                'icon' => 'icon-tools',
                'children' => [
                    [
                        'title' => '{LNG_Module settings}',
                        'url' => '/repair-settings',
                        'icon' => 'icon-cog'
                    ],
                    [
                        'title' => '{LNG_Repair status}',
                        'url' => '/repair-statuses?type=repairstatus',
                        'icon' => 'icon-tags'
                    ]
                ]
            ]
        ];

        return parent::insertMenuChildren($menus, $settingsMenu, 'settings', null, 2);
    }
}