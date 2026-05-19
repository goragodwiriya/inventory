<?php
/**
 * @filesource modules/inventory/controllers/category.php
 */

namespace Inventory\Category;

class Controller extends \Gcms\Category
{
    /**
     * Supported category types.
     *
     * @var array
     */
    protected $categories = [
        'category_id' => '{LNG_Category}',
        'type_id' => '{LNG_Type}',
        'model_id' => '{LNG_Brand}',
        'unit' => '{LNG_Unit}'
    ];
}