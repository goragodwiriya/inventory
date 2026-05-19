<?php
/**
 * @filesource modules/inventory/models/assets.php
 */

namespace Inventory\Assets;

use Kotchasan\Database\Sql;

class Model extends \Kotchasan\Model
{
    /**
     * Query data for asset table.
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [];
        if (!empty($params['holder_id'])) {
            $where[] = ['I.holder_id', $params['holder_id']];
        }
        if ($params['category_id'] !== '') {
            $where[] = ['V.category_id', $params['category_id']];
        }
        if ($params['type_id'] !== '') {
            $where[] = ['V.type_id', $params['type_id']];
        }
        if ($params['model_id'] !== '') {
            $where[] = ['V.model_id', $params['model_id']];
        }
        if ($params['inuse'] !== '') {
            $where[] = ['V.inuse', (int) $params['inuse']];
        }

        return static::createQuery()
            ->select(
                'V.id',
                'V.topic',
                'V.category_id',
                'V.type_id',
                'V.model_id',
                'V.inuse',
                Sql::MIN('I.product_no', 'first_product_no'),
                Sql::MIN('I.unit', 'unit'),
                Sql::COUNT('I.product_no', 'item_count'),
                Sql::SUM('I.stock', 'total_stock')
            )
            ->from('inventory V')
            ->join('inventory_items I', ['I.inventory_id', 'V.id'], 'LEFT')
            ->where($where)
            ->groupBy('V.id');
    }
}
