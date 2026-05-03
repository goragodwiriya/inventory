<?php
/**
 * @filesource modules/inventory/controllers/myassets.php
 */

namespace Inventory\Myassets;

use Kotchasan\Http\Request;

class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns.
     *
     * @var array
     */
    protected $allowedSortColumns = [
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
            'holder_id' => (string) $login->id,
            'category_id' => $request->get('category_id')->topic(),
            'type_id' => $request->get('type_id')->topic(),
            'model_id' => $request->get('model_id')->topic(),
            'inuse' => $request->get('inuse')->filter('01')
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
        return \Inventory\Holders\Model::toDataTable($params);
    }

    /**
     * Redirect the owner of an assigned asset to a prefilled repair request.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleRepairAction(Request $request, $login)
    {
        $productNo = trim($request->post('product_no')->topic());
        if ($productNo === '') {
            return $this->errorResponse('Invalid request', 400);
        }

        $assignment = \Inventory\Assignment\Model::getCurrent($productNo);
        if ($assignment === null || (int) $assignment->holder_id !== (int) $login->id) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/repair-request?product_no='.rawurlencode($productNo));
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
}