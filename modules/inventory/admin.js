EventManager.on('router:initialized', () => {
    RouterManager.register('/inventory-myassets', {
        template: 'inventory/myassets.html',
        title: '{LNG_My equipment}',
        requireAuth: true
    });

    RouterManager.register('/inventory-assets', {
        template: 'inventory/assets.html',
        title: '{LNG_Inventory}',
        requireAuth: true
    });

    RouterManager.register('/inventory-holders', {
        template: 'inventory/holders.html',
        title: '{LNG_Holder}',
        requireAuth: true
    });

    RouterManager.register('/inventory-asset', {
        template: 'inventory/asset-edit.html',
        title: '{LNG_Edit} {LNG_Inventory}',
        requireAuth: true
    });

    RouterManager.register('/inventory-items', {
        template: 'inventory/items.html',
        title: '{LNG_Item rows}',
        requireAuth: true
    });

    RouterManager.register('/inventory-categories', {
        template: 'inventory/categories.html',
        title: '{LNG_Category}',
        requireAuth: true
    });

    RouterManager.register('/inventory-settings', {
        template: 'inventory/settings.html',
        title: '{LNG_Module settings}',
        requireAuth: true
    });
});