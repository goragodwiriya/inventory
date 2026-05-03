<?php
/**
 * @filesource modules/inventory/models/asset.php
 */

namespace Inventory\Asset;

class Model extends \Kotchasan\Model
{
    /**
     * Get asset for editing.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get(int $id)
    {
        $record = (object) [
            'id' => 0,
            'topic' => '',
            'category_id' => '',
            'type_id' => '',
            'model_id' => '',
            'inuse' => 1,
            'detail' => '',
            'location' => '',
            'product_no' => '',
            'holder_id' => '',
            'unit' => '',
            'stock' => 1,
            'inventory' => []
        ];

        if ($id > 0) {
            $asset = static::createQuery()
                ->select('id', 'topic', 'category_id', 'type_id', 'model_id', 'inuse')
                ->from('inventory')
                ->where(['id', $id])
                ->first();

            if (!$asset) {
                return null;
            }

            $item = static::createQuery()
                ->select('product_no', 'unit', 'stock')
                ->from('inventory_items')
                ->where(['inventory_id', $id])
                ->orderBy('product_no')
                ->first();

            $record = (object) array_merge(
                (array) $record,
                (array) $asset,
                self::getMetaValues($id),
                $item ? (array) $item : []
            );
            $record->inventory = \Download\Index\Controller::getAttachments($id, 'inventory', self::$cfg->img_typies);
        }

        return $record;
    }

    /**
     * Save asset.
     *
     * @param int $id
     * @param array $save
     * @param array $meta
     * @param array $item
     *
     * @return int
     */
    public static function save(int $id, array $save, array $meta, array $item): int
    {
        $db = \Kotchasan\DB::create();

        if ($id === 0) {
            $id = (int) $db->insert('inventory', $save);
            $db->insert('inventory_items', [
                'inventory_id' => $id,
                'product_no' => $item['product_no'],
                'unit' => $item['unit'],
                'stock' => $item['stock']
            ]);

            \Inventory\Assignment\Model::setCurrent(
                $item['product_no'],
                isset($item['holder_id']) && ctype_digit((string) $item['holder_id']) && $item['holder_id'] !== '' ? (int) $item['holder_id'] : null,
                (float) $item['stock']
            );
        } else {
            $db->update('inventory', ['id', $id], $save);
        }

        self::saveMeta($id, $meta);

        return $id;
    }

    /**
     * Remove assets.
     *
     * @param array $ids
     *
     * @return int
     */
    public static function remove(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $productNos = static::createQuery()
            ->select('product_no')
            ->from('inventory_items')
            ->where(['inventory_id', $ids])
            ->fetchAll();

        $productNos = array_map(static function ($row) {
            return (string) $row->product_no;
        }, $productNos);

        if (!empty($productNos)) {
            $db->delete('inventory_assignments', ['product_no', $productNos], 0);
        }

        $db->delete('inventory_meta', ['inventory_id', $ids], 0);
        $db->delete('inventory_items', ['inventory_id', $ids], 0);
        $removed = $db->delete('inventory', ['id', $ids], 0);

        foreach ($ids as $id) {
            \Kotchasan\File::removeDirectory(ROOT_PATH.DATA_FOLDER.'inventory/'.$id.'/');
        }

        return (int) $removed;
    }

    /**
     * Check which assets cannot be deleted because they are linked to repair rows.
     *
     * @param array $ids
     *
     * @return array
     */
    public static function getDeleteBlockedIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $rows = static::createQuery()
            ->select('I.inventory_id')
            ->from('inventory_items I')
            ->join('repair R', ['R.product_no', 'I.product_no'], 'INNER')
            ->where(['I.inventory_id', $ids])
            ->groupBy('I.inventory_id')
            ->fetchAll();

        return array_map(static function ($row) {
            return (int) $row->inventory_id;
        }, $rows);
    }

    /**
     * Toggle inuse flag.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function toggleInuse(int $id)
    {
        $db = \Kotchasan\DB::create();
        $asset = $db->first('inventory', ['id', $id]);
        if (!$asset) {
            return null;
        }

        $inuse = (int) $asset->inuse === 1 ? 0 : 1;
        $db->update('inventory', ['id', $id], ['inuse' => $inuse]);
        $asset->inuse = $inuse;

        return $asset;
    }

    /**
     * Raw record.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function getRecord(int $id)
    {
        return static::createQuery()
            ->select('id', 'topic', 'inuse')
            ->from('inventory')
            ->where(['id', $id])
            ->first();
    }

    /**
     * Load inventory item rows for an asset.
     *
     * @param int $id
     *
     * @return array
     */
    public static function getItemRows(int $id): array
    {
        if ($id <= 0) {
            return [];
        }

        return static::createQuery()
            ->select('product_no', 'unit', 'stock')
            ->from('inventory_items')
            ->where(['inventory_id', $id])
            ->orderBy('product_no')
            ->fetchAll();
    }

    /**
     * Inventory item options for serial autocomplete.
     *
     * @param string|null $includeProductNo
     *
     * @return array
     */
    public static function getItemOptions(?string $includeProductNo = null): array
    {
        $rows = static::createQuery()
            ->select('I.product_no', 'I.inventory_id', 'I.unit', 'I.stock', 'V.topic')
            ->from('inventory_items I')
            ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
            ->orderBy('I.product_no')
            ->fetchAll();

        $options = array_map(static function ($row) {
            return [
                'value' => (string) $row->product_no,
                'text' => trim((string) $row->product_no.' - '.(string) $row->topic),
                'inventory_id' => (string) $row->inventory_id,
                'topic' => (string) ($row->topic ?? ''),
                'unit' => (string) ($row->unit ?? ''),
                'stock' => (float) ($row->stock ?? 0)
            ];
        }, $rows);

        if ($includeProductNo !== null && $includeProductNo !== '') {
            foreach ($options as $item) {
                if ($item['value'] === $includeProductNo) {
                    return $options;
                }
            }

            $row = static::createQuery()
                ->select('I.product_no', 'I.inventory_id', 'I.unit', 'I.stock', 'V.topic')
                ->from('inventory_items I')
                ->join('inventory V', ['V.id', 'I.inventory_id'], 'LEFT')
                ->where(['I.product_no', $includeProductNo])
                ->first();

            if ($row) {
                array_unshift($options, [
                    'value' => (string) $row->product_no,
                    'text' => trim((string) $row->product_no.' - '.(string) $row->topic),
                    'inventory_id' => (string) $row->inventory_id,
                    'topic' => (string) ($row->topic ?? ''),
                    'unit' => (string) ($row->unit ?? ''),
                    'stock' => (float) ($row->stock ?? 0)
                ]);
            }
        }

        return $options;
    }

    /**
     * Look for duplicate serial number.
     *
     * @param string $productNo
     * @param int $inventoryId
     *
     * @return object|null
     */
    public static function findDuplicateProductNo(string $productNo, int $inventoryId = 0)
    {
        if ($productNo === '') {
            return null;
        }

        $query = static::createQuery()
            ->select('product_no', 'inventory_id')
            ->from('inventory_items')
            ->where(['product_no', $productNo]);

        if ($inventoryId > 0) {
            $query->where(['inventory_id', '!=', $inventoryId]);
        }

        return $query->first();
    }

    /**
     * Load meta fields into flat array.
     *
     * @param int $id
     *
     * @return array
     */
    public static function getMetaValues(int $id): array
    {
        $rows = static::createQuery()
            ->select('name', 'value')
            ->from('inventory_meta')
            ->where(['inventory_id', $id])
            ->fetchAll();

        $meta = [];
        foreach ($rows as $row) {
            $meta[$row->name] = $row->value;
        }

        return $meta;
    }

    /**
     * Member options for holder autocomplete.
     *
     * @return array
     */
    public static function getHolderOptions(): array
    {
        return array_map(static function ($item) {
            return [
                'value' => (string) $item->value,
                'text' => (string) $item->text
            ];
        }, \Index\Users\Model::toOptions());
    }

    /**
     * Save meta values.
     *
     * @param int $id
     * @param array $meta
     *
     * @return void
     */
    public static function saveMeta(int $id, array $meta): void
    {
        $db = \Kotchasan\DB::create();
        $db->delete('inventory_meta', ['inventory_id', $id], 0);

        foreach ($meta as $name => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            $db->insert('inventory_meta', [
                'inventory_id' => $id,
                'name' => $name,
                'value' => $value
            ]);
        }
    }
}