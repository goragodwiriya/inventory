<?php
/**
 * @filesource modules/repair/controllers/status.php
 */

namespace Repair\Status;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Save a new repair status log.
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
            if (!\Repair\Helper\Controller::canProcessRepair($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $repairId = $request->post('id')->toInt();
            $row = Model::getRepairRow($repairId);
            if ($row === null || !\Repair\Helper\Controller::canViewRepair($login, $row)) {
                return $this->errorResponse('No data available', 404);
            }

            $save = [
                'repair_id' => $repairId,
                'status' => $request->post('status')->toInt(),
                'operator_id' => $request->post('operator_id')->toInt(),
                'comment' => $request->post('comment')->textarea(),
                'member_id' => (int) $login->id,
                'created_at' => date('Y-m-d H:i:s'),
                'cost' => max(0, $request->post('cost')->toDouble())
            ];
            if ($save['status'] <= 0) {
                return $this->formErrorResponse(['status' => 'Please select'], 400);
            }
            if (!\Repair\Helper\Controller::canManageRepair($login)) {
                $save['operator_id'] = (int) $login->id;
            }

            Model::saveStatus($repairId, $save);

            \Index\Log\Model::add($repairId, 'repair', 'Status', 'Updated repair status: '.$repairId, $login->id, $save['comment'], [
                'status' => $save['status'],
                'operator_id' => $save['operator_id'],
                'cost' => $save['cost']
            ]);

            $emailClass = '\\Repair\\Email\\Controller';
            $message = class_exists($emailClass) ? $emailClass::sendByRequestId($repairId) : 'Saved successfully';

            return $this->redirectResponse('reload', $message, 200, 0, 'table');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}