<?php
/**
 * @filesource modules/inventory/controllers/holders.php
 */

namespace Inventory\Holders;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns.
     *
     * @var array
     */
    protected $allowedSortColumns = ['topic', 'product_no', 'holder_id', 'unit', 'quantity', 'category_id', 'type_id', 'model_id'];

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
            'holder_id' => $request->get('holder_id')->topic(),
            'category_id' => $request->get('category_id')->topic(),
            'type_id' => $request->get('type_id')->topic(),
            'model_id' => $request->get('model_id')->topic(),
            'inuse' => $request->get('inuse')->number()
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
            'holder_id' => \Inventory\Asset\Model::getHolderOptions(),
            'inuse' => [
                ['value' => '', 'text' => '{LNG_All items}'],
                ['value' => '1', 'text' => '{LNG_Active}'],
                ['value' => '0', 'text' => '{LNG_Inactive}']
            ]
        ];
    }

    /**
     * Open holder edit modal for a specific item.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $productNo = $request->post('id')->topic();
        $response = \Inventory\Holder\Model::buildModalResponse($productNo);

        if ($response === null) {
            return $this->errorResponse('No data available', 404);
        }

        $response['actions'] = [
            [
                'type' => 'modal',
                'action' => 'show',
                'template' => '/inventory/holder.html',
                'title' => '{LNG_Edit} {LNG_Holder}',
                'titleClass' => 'icon-user'
            ]
        ];

        return $this->successResponse($response, 'Holder data retrieved');
    }

    /**
     * Clear holder assignment from an item.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleClearholdAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_inventory', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        $productNo = $request->post('id')->topic();
        if ($productNo === '') {
            return $this->errorResponse('No data available', 400);
        }

        \Inventory\Holder\Model::clearHolder($productNo);

        \Index\Log\Model::add(0, 'inventory', 'Holder', 'Cleared holder assignment for item: '.$productNo, $login->id);

        return $this->redirectResponse('reload', 'Holder cleared', 200, 0, 'table');
    }
}
