<?php
/**
 * @filesource modules/inventory/models/holder.php
 */

namespace Inventory\Holder;

class Model extends \Kotchasan\Model
{
    /**
     * Load holder form data for a single item.
     * Returns null when product_no is given but the item does not exist.
     *
     * @param string $productNo  Empty string = select-existing mode.
     *
     * @return object|null
     */
    public static function get(string $productNo)
    {
        if ($productNo !== '') {
            $item = static::createQuery()
                ->select('I.product_no', 'I.inventory_id', 'I.unit', 'I.stock', 'V.topic')
                ->from('inventory_items I')
                ->join('inventory V', ['V.id', 'I.inventory_id'])
                ->where(['I.product_no', $productNo])
                ->first();

            if (!$item) {
                return null;
            }

            $assignment = \Inventory\Assignment\Model::getCurrent($productNo);

            return (object) [
                'original_product_no' => $productNo,
                'product_no' => (string) $item->product_no,
                'inventory_id' => (string) $item->inventory_id,
                'topic' => (string) $item->topic,
                'holder_id' => $assignment !== null ? (string) $assignment->holder_id : '',
                'unit' => (string) ($item->unit ?? ''),
                'stock' => (float) $item->stock,
                'quantity' => $assignment !== null ? (float) $assignment->quantity : 1
            ];
        }

        return (object) [
            'original_product_no' => '',
            'product_no' => '',
            'inventory_id' => '',
            'topic' => '',
            'holder_id' => '',
            'unit' => '',
            'stock' => 1,
            'quantity' => 1
        ];
    }

    /**
     * Build the complete data + options payload for the holder modal.
     * Returns null when product_no is given but the item does not exist.
     *
     * @param string $productNo
     *
     * @return array|null
     */
    public static function buildModalResponse(string $productNo): ?array
    {
        $data = self::get($productNo);
        if ($data === null) {
            return null;
        }

        return [
            'data' => $data,
            'options' => [
                'product_no' => \Inventory\Asset\Model::getItemOptions($productNo !== '' ? $productNo : null),
                'holder_id' => \Inventory\Asset\Model::getHolderOptions()
            ]
        ];
    }

    /**
     * Save holder assignment for an existing item.
     *
     * @param string $productNo
     * @param string $holderIdStr
     * @param float  $quantity
     *
     * @return string  The product_no that was saved.
     */
    public static function save(
        string $productNo,
        string $holderIdStr,
        float $quantity
    ): string {
        $holderId = $holderIdStr !== '' && ctype_digit($holderIdStr) ? (int) $holderIdStr : null;

        \Inventory\Assignment\Model::setCurrent($productNo, $holderId, $quantity);

        return $productNo;
    }

    /**
     * Remove holder assignment from an item.
     *
     * @param string $productNo
     */
    public static function clearHolder(string $productNo): void
    {
        \Inventory\Assignment\Model::clearCurrent($productNo);
    }
}
