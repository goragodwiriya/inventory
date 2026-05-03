<?php
/**
 * @filesource modules/repair/controllers/history.php
 */

namespace Repair\History;

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
        'appointment_date'
    ];

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
     * Request filters.
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'status' => $request->get('status')->number()
        ];
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
            $item->can_edit = \Repair\Helper\Controller::canEditRequest($login, $item, isset($item->status) ? (int) $item->status : null);
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
            'status' => \Repair\Helper\Controller::getStatusOptions()
        ];
    }

    /**
     * Open history detail modal.
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

        return $this->successResponse([
            'data' => $payload,
            'options' => [
                'status' => \Repair\Helper\Controller::getStatusOptions(),
                'operator_id' => \Repair\Helper\Controller::getOperatorOptions((int) ($payload->operator_id ?? 0))
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
     * Redirect to edit request.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $row = \Repair\Repair\Model::getRecord($id);
        $latest = \Repair\Helper\Controller::getLatestStatusMap([$id])[$id] ?? null;
        $latestStatusId = $latest ? (int) $latest->status : null;
        if ($row === null || !\Repair\Helper\Controller::canEditRequest($login, $row, $latestStatusId)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/repair-request?id='.$id);
    }
}