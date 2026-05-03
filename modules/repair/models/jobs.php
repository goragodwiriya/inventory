<?php
/**
 * @filesource modules/repair/models/jobs.php
 */

namespace Repair\Jobs;

use Kotchasan\Database\Sql;

class Model extends \Kotchasan\Model
{
    /**
     * Distinct operator IDs already used in repair statuses.
     *
     * @return array
     */
    public static function getAssignedOperatorIds(): array
    {
        $rows = static::createQuery()
            ->select('operator_id')
            ->from('repair_status')
            ->where(['operator_id', '>', 0])
            ->groupBy('operator_id')
            ->fetchAll();

        return array_values(array_unique(array_filter(array_map(static function ($row) {
            return (int) ($row->operator_id ?? 0);
        }, $rows))));
    }

    /**
     * Query repair jobs for officers.
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params, $login)
    {
        $where = [];
        if ($params['status'] !== '') {
            $where[] = ['S.status', $params['status']];
        }
        if ($params['operator_id'] !== 0) {
            $where[] = ['S.operator_id', $params['operator_id']];
        }

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
                'U.name customer_name',
                'U.phone customer_phone',
                'S.operator_id',
                'S.status'
            )
            ->from('repair R')
            ->join('inventory_items I', ['I.product_no', 'R.product_no'], 'LEFT')
            ->join('repair_status S', ['S.id', $q1], 'LEFT')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->join('user U', ['U.id', 'R.customer_id'], 'LEFT')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['R.job_id', 'LIKE', $search],
                ['R.product_no', 'LIKE', $search],
                ['V.topic', 'LIKE', $search],
                ['R.job_description', 'LIKE', $search],
                ['U.name', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}