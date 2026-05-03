<?php
/**
 * @filesource modules/repair/controllers/jobs.php
 */

namespace Repair\Jobs;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns.
     *
     * @var array
     */
    protected $allowedSortColumns = [
        'job_id',
        'product_no',
        'topic',
        'created_at',
        'appointment_date',
        'customer_name'
    ];

    /**
     * Authorization check.
     *
     * @param Request $request
     * @param object $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!\Repair\Helper\Controller::canProcessRepair($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Get custom parameters for users table
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'status' => $request->get('status')->number(),
            'operator_id' => $request->get('operator_id')->number()
        ];
    }

    /**
     * Query data.
     *
     * @param array $params
     * @param object|null $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return Model::toDataTable($params, $login);
    }

    /**
     * Format rows.
     *
     * @param array $datas
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        foreach ($datas as $item) {
            $item->can_process = \Repair\Helper\Controller::canProcessRepair($login);
            $item->can_edit = \Repair\Helper\Controller::canManageRepair($login);
        }

        return $datas;
    }

    /**
     * Get filters for table response
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $operatorIds = call_user_func([\Repair\Jobs\Model::class, 'getAssignedOperatorIds']);

        return [
            'status' => \Repair\Helper\Controller::getStatusOptions(),
            'operator_id' => call_user_func([\Repair\Helper\Controller::class, 'getOperatorOptionsByIds'], $operatorIds)
        ];
    }

    /**
     * Open detail modal.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleViewAction(Request $request, $login)
    {
        $payload = \Repair\Status\Model::getModalPayload($request->post('id')->toInt(), $login, false);
        if ($payload === null) {
            return $this->errorResponse('No data available', 404);
        }
        $operatorIds = [(int) ($payload->operator_id ?? 0), (int) ($payload->customer_id ?? 0)];
        $operatorOptions = call_user_func([\Repair\Helper\Controller::class, 'getOperatorOptionsByIds'], $operatorIds);

        return $this->successResponse([
            'data' => $payload,
            'options' => [
                'status' => \Repair\Helper\Controller::getStatusOptions(),
                'operator_id' => $operatorOptions
            ],
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => '/repair/status.html',
                    'title' => '{LNG_Repair job description}',
                    'titleClass' => 'icon-tools'
                ]
            ]
        ], 'Repair details retrieved');
    }

    /**
     * Open process status modal.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        $payload = \Repair\Status\Model::getModalPayload($request->post('id')->toInt(), $login, true);
        if ($payload === null) {
            return $this->errorResponse('No data available', 404);
        }

        $canManageRepair = \Repair\Helper\Controller::canManageRepair($login);
        if (!$canManageRepair) {
            $payload->operator_id = (int) $login->id;
        }
        $operatorOptions = $canManageRepair
            ? call_user_func([\Repair\Helper\Controller::class, 'getOperatorOptionsByIds'], [(int) ($payload->operator_id ?? 0), (int) ($payload->customer_id ?? 0)])
            : [[
            'value' => (string) $login->id,
            'text' => trim((string) (($login->name ?? '') !== '' ? $login->name : (($login->username ?? '') !== '' ? $login->username : $login->id)))
        ]];

        return $this->successResponse([
            'data' => $payload,
            'options' => [
                'status' => \Repair\Helper\Controller::getStatusOptions(),
                'operator_id' => $operatorOptions
            ],
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => '/repair/status.html',
                    'title' => '{LNG_Repair status}',
                    'titleClass' => 'icon-tools'
                ]
            ]
        ], 'Repair status form loaded');
    }

    /**
     * Redirect to edit request for managers.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_manage_repair', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/repair-request?id='.$request->post('id')->toInt());
    }
}