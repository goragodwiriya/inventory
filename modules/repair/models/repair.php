<?php
/**
 * @filesource modules/repair/models/repair.php
 */

namespace Repair\Repair;

class Model extends \Kotchasan\Model
{
    /**
     * Get repair request data.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get(int $id)
    {
        $record = (object) [
            'id' => 0,
            'customer_id' => 0,
            'product_no' => '',
            'job_id' => '',
            'job_description' => '',
            'appointment_date' => '',
            'comment' => '',
            'latest_status_text' => ''
        ];

        if ($id > 0) {
            $row = static::createQuery()
                ->select('id', 'customer_id', 'product_no', 'job_id', 'job_description', 'appointment_date')
                ->from('repair')
                ->where(['id', $id])
                ->first();
            if (!$row) {
                return null;
            }

            $record = (object) array_merge((array) $record, (array) $row);
        }

        return $record;
    }

    /**
     * Save repair request.
     *
     * @param int $id
     * @param array $save
     * @param array|null $statusLog
     *
     * @return int
     */
    public static function save(int $id, array $save, ?array $statusLog = null): int
    {
        $db = \Kotchasan\DB::create();

        if ($id > 0) {
            $db->update('repair', ['id', $id], $save);
        } else {
            $id = (int) $db->insert('repair', $save);
            if ($statusLog !== null) {
                $statusLog['repair_id'] = $id;
                $db->insert('repair_status', $statusLog);
            }
        }

        return $id;
    }

    /**
     * Remove repair requests and their status history.
     *
     * @param array $ids
     *
     * @return int
     */
    public static function remove(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $db->delete('repair_status', ['repair_id', $ids], 0);

        return (int) $db->delete('repair', ['id', $ids], 0);
    }

    /**
     * Get a repair record by ID.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function getRecord(int $id)
    {
        return static::createQuery()
            ->select('id', 'customer_id', 'product_no', 'job_id', 'job_description', 'appointment_date')
            ->from('repair')
            ->where(['id', $id])
            ->first();
    }

    /**
     * Validate selected inventory item.
     *
     * @param string $productNo
     *
     * @return object|null
     */
    public static function getInventoryItem(string $productNo)
    {
        if ($productNo === '') {
            return null;
        }

        return static::createQuery()
            ->select('I.product_no', 'V.topic')
            ->from('inventory_items I')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->where(['I.product_no', $productNo])
            ->first();
    }
}