<?php
/**
 * @filesource modules/inventory/models/assignment.php
 */

namespace Inventory\Assignment;

class Model extends \Kotchasan\Model
{
    /**
     * Load the current active assignment for a product number.
     *
     * @param string $productNo
     *
     * @return object|null
     */
    public static function getCurrent(string $productNo)
    {
        if ($productNo === '') {
            return null;
        }

        return static::createQuery()
            ->select('id', 'product_no', 'holder_id', 'quantity', 'assigned_at')
            ->from('inventory_assignments')
            ->where(['product_no', $productNo])
            ->where(['returned_at', null])
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Close any current assignment and optionally create a new active assignment.
     *
     * @param string   $productNo
     * @param int|null $holderId
     * @param float    $quantity
     *
     * @return void
     */
    public static function setCurrent(string $productNo, ?int $holderId, float $quantity): void
    {
        if ($productNo === '') {
            return;
        }

        $now = date('Y-m-d H:i:s');

        static::createQuery()
            ->update('inventory_assignments')
            ->set(['returned_at' => $now])
            ->where(['product_no', $productNo])
            ->where(['returned_at', null])
            ->execute();

        if ($holderId === null || $quantity <= 0) {
            return;
        }

        \Kotchasan\DB::create()->insert('inventory_assignments', [
            'product_no' => $productNo,
            'holder_id' => $holderId,
            'quantity' => $quantity,
            'assigned_at' => $now,
            'returned_at' => null
        ]);
    }

    /**
     * Clear the current active assignment.
     *
     * @param string $productNo
     *
     * @return void
     */
    public static function clearCurrent(string $productNo): void
    {
        self::setCurrent($productNo, null, 0);
    }

    /**
     * Load current assigned quantities indexed by product_no.
     *
     * @param array $productNos
     *
     * @return array
     */
    public static function getCurrentQuantities(array $productNos): array
    {
        $productNos = array_values(array_filter(array_map('strval', $productNos), static function ($productNo) {
            return $productNo !== '';
        }));
        if (empty($productNos)) {
            return [];
        }

        $rows = static::createQuery()
            ->select('product_no', 'quantity')
            ->from('inventory_assignments')
            ->where(['product_no', $productNos])
            ->where(['returned_at', null])
            ->fetchAll();

        $quantities = [];
        foreach ($rows as $row) {
            $quantities[(string) $row->product_no] = (float) $row->quantity;
        }

        return $quantities;
    }

    /**
     * Product numbers that still have an active assignment under an inventory asset.
     *
     * @param int $inventoryId
     *
     * @return array
     */
    public static function getActiveProductNosByInventoryId(int $inventoryId): array
    {
        if ($inventoryId <= 0) {
            return [];
        }

        $rows = static::createQuery()
            ->select('A.product_no')
            ->from('inventory_assignments A')
            ->join('inventory_items I', ['I.product_no', 'A.product_no'])
            ->where(['I.inventory_id', $inventoryId])
            ->where(['A.returned_at', null])
            ->groupBy('A.product_no')
            ->fetchAll();

        return array_map(static function ($row) {
            return (string) $row->product_no;
        }, $rows);
    }
}