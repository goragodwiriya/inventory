<?php
/**
 * @filesource modules/repair/controllers/repair.php
 */

namespace Repair\Repair;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Load repair request data.
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

            $id = $request->get('id', 0)->toInt();
            $productNo = trim($request->get('product_no')->topic());
            $data = Model::get($id);
            if ($data === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            if ($id === 0 && $productNo !== '') {
                $assignment = \Inventory\Assignment\Model::getCurrent($productNo);
                if (
                    Model::getInventoryItem($productNo) !== null
                    && (
                        \Repair\Helper\Controller::canManageRepair($login)
                        || ($assignment !== null && (int) $assignment->holder_id === (int) $login->id)
                    )
                ) {
                    $data->product_no = $productNo;
                }
            }

            $latest = $id > 0 ? (\Repair\Helper\Controller::getLatestStatusMap([$id])[$id] ?? null) : null;
            $latestStatusId = $latest ? (int) $latest->status : null;
            if ($id > 0 && !\Repair\Helper\Controller::canEditRequest($login, $data, $latestStatusId)) {
                return $this->errorResponse('Permission required', 403);
            }

            $data->can_edit = $id === 0 || \Repair\Helper\Controller::canEditRequest($login, $data, $latestStatusId);
            $data->latest_status_text = $latest ? \Repair\Helper\Controller::getStatusText((int) $latest->status) : '';

            return $this->successResponse([
                'data' => $data,
                'options' => [
                    'product_no' => \Inventory\Asset\Model::getItemOptions($data->product_no)
                ]
            ], 'Repair request data loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save repair request.
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

            $id = $request->post('id', 0)->toInt();
            $row = $id > 0 ? Model::getRecord($id) : null;
            $latest = $id > 0 ? (\Repair\Helper\Controller::getLatestStatusMap([$id])[$id] ?? null) : null;
            $latestStatusId = $latest ? (int) $latest->status : null;
            if ($id > 0 && ($row === null || !\Repair\Helper\Controller::canEditRequest($login, $row, $latestStatusId))) {
                return $this->errorResponse('Permission required', 403);
            }

            $save = [
                'product_no' => $request->post('product_no')->topic(),
                'job_description' => $request->post('job_description')->textarea(),
                'appointment_date' => $request->post('appointment_date')->date(),
                'informer' => (string) ($login->name ?? '')
            ];
            $comment = trim($request->post('comment')->textarea());

            $errors = [];
            if ($save['product_no'] === '') {
                $errors['product_no'] = 'Please select';
            }
            if ($save['job_description'] === '') {
                $errors['job_description'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $product = Model::getInventoryItem($save['product_no']);
            if ($product === null) {
                return $this->formErrorResponse(['product_no' => 'Please select from the search results'], 400);
            }

            if ($id === 0) {
                $save['customer_id'] = (int) $login->id;
                $save['created_at'] = date('Y-m-d H:i:s');
                $save['job_id'] = \Index\Number\Model::get(
                    0,
                    (string) (self::$cfg->repair_job_no ?? 'JOB%04d'),
                    'repair',
                    'job_id',
                    (string) (self::$cfg->repair_prefix ?? '')
                );
                $save['repair_no'] = null;
                $save['appraiser'] = 0;

                $statusLog = [
                    'status' => \Repair\Helper\Controller::getFirstStatusId(),
                    'operator_id' => (int) $login->id,
                    'comment' => $comment,
                    'member_id' => (int) $login->id,
                    'created_at' => $save['created_at'],
                    'cost' => 0
                ];
            } else {
                $statusLog = null;
            }

            $id = Model::save($id, $save, $statusLog);

            \Index\Log\Model::add($id, 'repair', 'Save', 'Saved repair request: '.$id, $login->id);

            if ($row === null) {
                $emailClass = '\\Repair\\Email\\Controller';
                $message = class_exists($emailClass) ? $emailClass::sendByRequestId($id) : 'Saved successfully';
            } else {
                $message = 'Saved successfully';
            }

            return $this->redirectResponse(\Repair\Helper\Controller::canManageRepair($login) ? '/repair-jobs' : '/repair-history', $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}