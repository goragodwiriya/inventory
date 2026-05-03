<?php
/**
 * @filesource modules/inventory/controllers/assets.php
 */

namespace Inventory\Assets;

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
        'id',
        'topic',
        'category_id',
        'type_id',
        'model_id',
        'first_product_no',
        'item_count',
        'total_stock',
        'inuse'
    ];

    /**
     * Permission check.
     *
     * @param Request $request
     * @param object $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_manage_inventory', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
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
            'category_id' => $request->get('category_id')->topic(),
            'type_id' => $request->get('type_id')->topic(),
            'model_id' => $request->get('model_id')->topic(),
            'inuse' => $request->get('inuse')->filter('0-1')
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
        return Model::toDataTable($params);
    }

    /**
     * Filter definitions.
     *
     * @param array $params
     * @param object|null $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $categories = \Inventory\Category\Controller::init();

        return [
            'category_id' => $categories->toOptions('category_id', true, null, ['' => '{LNG_All items}']),
            'type_id' => $categories->toOptions('type_id', true, null, ['' => '{LNG_All items}']),
            'model_id' => $categories->toOptions('model_id', true, null, ['' => '{LNG_All items}']),
            'inuse' => [
                ['value' => '', 'text' => '{LNG_All items}'],
                ['value' => '1', 'text' => '{LNG_Active}'],
                ['value' => '0', 'text' => '{LNG_Inactive}']
            ]
        ];
    }

    /**
     * Get asset details for edit modal.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        if (\Inventory\Asset\Model::getRecord($id) === null) {
            return $this->errorResponse('No data available', 404);
        }

        return $this->redirectResponse('/inventory-asset?id='.$id);
    }

    /**
     * Open item rows modal.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleItemsAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        if (\Inventory\Asset\Model::getRecord($id) === null) {
            return $this->errorResponse('No data available', 404);
        }

        return $this->redirectResponse('/inventory-items?id='.$id);
    }

    /**
     * Delete assets.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_inventory', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No data to delete', 400);
        }

        $blocked = \Inventory\Asset\Model::getDeleteBlockedIds($ids);
        if (!empty($blocked)) {
            return $this->errorResponse('Some assets are referenced by repair records and cannot be deleted', 400);
        }

        $removed = \Inventory\Asset\Model::remove($ids);
        if ($removed === 0) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'inventory', 'Delete', 'Deleted inventory ID(s): '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removed.' inventory asset(s) successfully', 200, 0, 'table');
    }

    /**
     * Toggle active status.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleActiveAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_inventory', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $asset = \Inventory\Asset\Model::toggleInuse($request->post('id')->toInt());
        if ($asset === null) {
            return $this->errorResponse('No data available', 404);
        }

        \Index\Log\Model::add($asset->id, 'inventory', 'Status', ((int) $asset->inuse === 1 ? 'Activated asset' : 'Deactivated asset').': '.$asset->topic, $login->id);

        return $this->redirectResponse('reload', 'Saved successfully', 200, 0, 'table');
    }
}