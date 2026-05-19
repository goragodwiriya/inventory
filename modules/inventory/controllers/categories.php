<?php
/**
 * @filesource modules/inventory/controllers/categories.php
 */

namespace Inventory\Categories;

class Controller extends \Index\Category\Controller
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