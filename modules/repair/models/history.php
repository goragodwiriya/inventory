<?php
/**
 * @filesource modules/repair/models/history.php
 */

namespace Repair\History;

use Kotchasan\Database\Sql;

class Model extends \Kotchasan\Model
{
    /**
     * Query repair history for current member.
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params, $login)
    {
        $q1 = static::createQuery()
            ->select('id')
            ->from('repair_status')
            ->where(['repair_id', Sql::column('R.id')])
            ->orderBy('id', 'DESC')
            ->limit(1);

        $query = static::createQuery()
            ->select(
                'R.id',
                'R.customer_id',
                'R.job_id',
                'R.product_no',
                'R.job_description',
                'R.created_at',
                'R.appointment_date',
                'V.topic',
                'S.operator_id',
                'S.status'
            )
            ->from('repair R')
            ->join('inventory_items I', ['I.product_no', 'R.product_no'], 'LEFT')
            ->join('repair_status S', ['S.id', $q1], 'LEFT')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->where(['R.customer_id', (int) $login->id]);

        if ($params['status'] !== '') {
            $query->where(['S.status', $params['status']]);
        }

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['R.job_id', 'LIKE', $search],
                ['R.product_no', 'LIKE', $search],
                ['V.topic', 'LIKE', $search],
                ['R.job_description', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}