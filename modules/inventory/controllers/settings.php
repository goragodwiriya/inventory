<?php
/**
 * @filesource modules/inventory/controllers/settings.php
 */

namespace Inventory\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Get module settings.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, ['can_manage_inventory', 'can_config'])) {
                return $this->errorResponse('Forbidden', 403);
            }

            return $this->successResponse([
                'data' => (object) [
                    'inventory_w' => (int) (self::$cfg->inventory_w ?? 800),
                    'inventory_warranty_alert_days' => (int) (self::$cfg->inventory_warranty_alert_days ?? 30)
                ]
            ], 'Inventory settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save module settings.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }

            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->inventory_w = max(200, $request->post('inventory_w')->toInt());
            $config->inventory_warranty_alert_days = max(0, $request->post('inventory_warranty_alert_days')->toInt());

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                \Index\Log\Model::add(0, 'inventory', 'Save', 'Saved inventory settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }

            return $this->errorResponse('Failed to save settings', 500);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}