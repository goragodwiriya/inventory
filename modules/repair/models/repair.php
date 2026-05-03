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