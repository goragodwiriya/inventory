<?php
/**
 * @filesource modules/inventory/models/items.php
 */

namespace Inventory\Items;

class Model extends \Kotchasan\Model
{
    /**
     * Load item-row editor payload for an asset.
     *
     * @param int $inventoryId
     *
     * @return object|null
     */
    public static function get(int $inventoryId)
    {
        $asset = \Inventory\Asset\Model::getRecord($inventoryId);
        if ($asset === null) {
            return null;
        }

        $asset->item_rows = [
            //'columns' => self::getColumns(),
            'data' => self::getRows($inventoryId),
            'options' => [
                'unit' => \Inventory\Category\Controller::init()->toOptions('unit', false, null, ['' => '{LNG_Please select}'])
            ]
        ];

        return $asset;
    }

    /**
     * Column metadata for the editable item rows table.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            [
                'field' => 'product_no',
                'label' => '{LNG_Serial}/{LNG_Registration No}',
                'cellElement' => 'text',
                'i18n' => true
            ],
            [
                'field' => 'stock',
                'label' => 'Stock',
                'cellElement' => 'number',
                'min' => 0,
                'step' => '0.01',
                'class' => 'center',
                'cellClass' => 'center',
                'i18n' => true
            ],
            [
                'field' => 'unit',
                'label' => 'Unit',
                'cellElement' => 'select',
                'optionsKey' => 'unit',
                'i18n' => true
            ]
        ];
    }

    /**
     * Load item rows for editing.
     *
     * @param int $inventoryId
     *
     * @return array
     */
    public static function getRows(int $inventoryId): array
    {
        $rows = [];
        $index = 0;
        foreach (\Inventory\Asset\Model::getItemRows($inventoryId) as $row) {
            ++$index;
            $rows[] = [
                'id' => 'row_'.$index,
                'product_no' => (string) $row->product_no,
                'unit' => (string) ($row->unit ?? ''),
                'stock' => (float) $row->stock + 0
            ];
        }

        return $rows;
    }

    /**
     * Product numbers that already have repair references.
     *
     * @param int $inventoryId
     *
     * @return array
     */
    public static function getRepairLinkedProductNumbers(int $inventoryId): array
    {
        $rows = static::createQuery()
            ->select('I.product_no')
            ->from('inventory_items I')
            ->join('repair R', ['R.product_no', 'I.product_no'], 'INNER')
            ->where(['I.inventory_id', $inventoryId])
            ->groupBy('I.product_no')
            ->fetchAll();

        return array_map(static function ($row) {
            return (string) $row->product_no;
        }, $rows);
    }

    /**
     * Replace all item rows for an asset.
     *
     * @param int $inventoryId
     * @param array $rows
     *
     * @return void
     */
    public static function replace(int $inventoryId, array $rows): void
    {
        $db = \Kotchasan\DB::create();
        $db->delete('inventory_items', ['inventory_id', $inventoryId], 0);

        foreach ($rows as $row) {
            $db->insert('inventory_items', [
                'inventory_id' => $inventoryId,
                'product_no' => $row['product_no'],
                'unit' => $row['unit'],
                'stock' => $row['stock']
            ]);
        }
    }
}