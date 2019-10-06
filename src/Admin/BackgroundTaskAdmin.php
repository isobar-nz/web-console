<?php

namespace TractorCow\WebConsole\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Security\Permission;
use TractorCow\WebConsole\Model\BackgroundTask;

class BackgroundTaskAdmin extends ModelAdmin
{
    private static $url_segment = 'webconsole-tasks';

    private static $menu_title = 'Web Console Tasks';

    public function canView($member = null)
    {
        // Only root admins can access this
        return Permission::checkMember($member, 'ADMIN');
    }

    private static $managed_models = [
        BackgroundTask::class,
    ];
}
