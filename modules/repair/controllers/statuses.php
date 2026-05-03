<?php
/**
 * @filesource modules/repair/controllers/statuses.php
 */

namespace Repair\Statuses;

class Controller extends \Index\Categories\Controller
{
    /**
     * Supported category types.
     *
     * @var array
     */
    protected $categories = [
        'repairstatus' => '{LNG_Repair status}'
    ];
}