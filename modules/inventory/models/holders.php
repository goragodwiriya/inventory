<?php
/**
 * @filesource modules/inventory/models/holders.php
 */

namespace Inventory\Holders;

class Model extends \Kotchasan\Model
{
    /**
     * Query data for holders table — item-level rows that have a holder assigned.
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [
            ['A.returned_at', null]
        ];

        if (!empty($params['holder_id'])) {
            $where[] = ['A.holder_id', (int) $params['holder_id']];
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
            $where[] = ['V.inuse', $params['inuse']];
        }

        return static::createQuery()
            ->select(
                'I.product_no id',
                'I.product_no',
                'I.inventory_id',
                'A.holder_id',
                'A.quantity quantity',
                'I.unit',
                'V.topic',
                'V.category_id',
                'V.type_id',
                'V.model_id',
                'V.inuse'
            )
            ->from('inventory_assignments A')
            ->join('inventory_items I', ['I.product_no', 'A.product_no'])
            ->join('inventory V', ['V.id', 'I.inventory_id'])
            ->where($where);
    }
}
