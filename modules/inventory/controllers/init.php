<?php
/**
 * @filesource modules/inventory/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Inventory\Init;

use Gcms\Api as ApiController;

/**
 * Init Controller for Inventory Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Register inventory permissions.
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
            'value' => 'can_manage_inventory',
            'text' => '{LNG_Can manage} {LNG_Inventory}'
        ];

        return $permissions;
    }

    /**
     * Register inventory menus.
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
                'title' => '{LNG_My equipment}',
                'url' => '/inventory-myassets',
                'icon' => 'icon-product'
            ]
        ];
        $menus = parent::insertMenuAfter($menus, $memberMenu, 0);

        if (!ApiController::hasPermission($login, ['can_manage_inventory', 'can_config'])) {
            return $menus;
        }

        $children = [];
        if (ApiController::hasPermission($login, ['can_manage_inventory', 'can_config'])) {
            $children[] = [
                'title' => '{LNG_Inventory}',
                'url' => '/inventory-assets',
                'icon' => 'icon-product'
            ];
            $children[] = [
                'title' => '{LNG_Holder}',
                'url' => '/inventory-holders',
                'icon' => 'icon-user'
            ];
            foreach (\Inventory\Category\Controller::items() as $key => $title) {
                $children[] = [
                    'title' => $title,
                    'url' => '/inventory-categories?type='.$key,
                    'icon' => 'icon-tags'
                ];
            }
        }
        if (ApiController::hasPermission($login, 'can_config')) {
            $children[] = [
                'title' => '{LNG_Module settings}',
                'url' => '/inventory-settings',
                'icon' => 'icon-cog'
            ];
        }

        if (empty($children)) {
            return $menus;
        }

        $settingsMenu = [
            [
                'title' => '{LNG_Inventory}',
                'icon' => 'icon-product',
                'children' => $children
            ]
        ];

        return parent::insertMenuChildren($menus, $settingsMenu, 'settings', null, 1);
    }
}
