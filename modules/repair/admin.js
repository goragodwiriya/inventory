EventManager.on('router:initialized', () => {
    RouterManager.register('/', {
        template: 'repair/dashboard.html',
        title: '{LNG_Dashboard}',
        requireAuth: true
    });

    RouterManager.register('/repair-request', {
        template: 'repair/request.html',
        title: '{LNG_Get a repair}',
        requireAuth: true
    });

    RouterManager.register('/repair-history', {
        template: 'repair/history.html',
        title: '{LNG_Repair history}',
        requireAuth: true
    });

    RouterManager.register('/repair-jobs', {
        template: 'repair/jobs.html',
        title: '{LNG_Repair jobs}',
        requireAuth: true
    });

    RouterManager.register('/repair-settings', {
        template: 'repair/settings.html',
        title: '{LNG_Module settings}',
        requireAuth: true
    });

    RouterManager.register('/repair-statuses', {
        template: 'repair/statuses.html',
        title: '{LNG_Repair status}',
        requireAuth: true
    });
});