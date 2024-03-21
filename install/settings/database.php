<?php
/* settings/database.php */

return array(
    'mysql' => array(
        'dbdriver' => 'mysql',
        'username' => 'root',
        'password' => '',
        'dbname' => 'inventory',
        'prefix' => 'app'
    ),
    'tables' => array(
        'category' => 'category',
        'language' => 'language',
        'logs' => 'logs',
        'inventory' => 'inventory',
        'inventory_meta' => 'inventory_meta',
        'inventory_items' => 'inventory_items',
        'repair' => 'repair',
        'repair_status' => 'repair_status',
        'user' => 'user',
        'user_meta' => 'user_meta'
    )
);
