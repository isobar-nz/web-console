<?php

namespace TractorCow\WebConsole\Admin;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Security\Permission;

class ConsoleAdmin extends LeftAndMain
{
    private static $url_segment = 'webconsole';

    private static $menu_title = 'Web Console';

    public function canView($member = null)
    {
        // Only root admins can access this
        return Permission::checkMember($member, 'ADMIN');
    }
}
