<?php
/**
 * @filesource modules/repair/controllers/settings.php
 */

namespace Repair\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Load repair module settings.
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
            if (!ApiController::hasPermission($login, ['can_manage_repair', 'can_config'])) {
                return $this->errorResponse('Forbidden', 403);
            }

            return $this->successResponse([
                'data' => (object) [
                    'repair_first_status' => (int) (self::$cfg->repair_first_status ?? \Repair\Helper\Controller::getFirstStatusId()),
                    'repair_prefix' => (string) (self::$cfg->repair_prefix ?? ''),
                    'repair_job_no' => (string) (self::$cfg->repair_job_no ?? 'JOB%04d')
                ],
                'options' => [
                    'repair_first_status' => \Repair\Helper\Controller::getStatusOptions()
                ]
            ], 'Repair settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save repair module settings.
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
            $config->repair_first_status = $request->post('repair_first_status')->toInt();
            $config->repair_prefix = $request->post('repair_prefix')->topic();
            $config->repair_job_no = $request->post('repair_job_no')->topic();

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                \Index\Log\Model::add(0, 'repair', 'Save', 'Saved repair settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }

            return $this->errorResponse('Failed to save settings', 500);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}