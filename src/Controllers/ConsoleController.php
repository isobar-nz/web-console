<?php

namespace TractorCow\WebConsole\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class ConsoleController extends Controller
{
    private static $url_segment = 'webconsole';

    public function index()
    {
        if (!Permission::check('ADMIN')) {
            return Security::permissionFailure($this, 'You must be admin to use this module');
        }
        return $this;
    }

    private static $allowed_actions = [
        'callback',
    ];

    public function callback()
    {
        if (!Permission::check('ADMIN')) {
            return Security::permissionFailure($this, 'You must be admin to use this module');
        }

        // Permission approved
        global $NO_LOGIN;
        $NO_LOGIN = true;

        global $HOME_DIRECTORY;
        $HOME_DIRECTORY = BASE_PATH;

        // Handle passthrough
        include __DIR__ . '/../../webconsole/src/webconsole.includes.php';
        include __DIR__ . '/../../webconsole/src/webconsole.main.php';

        // This is important; This library isn't designed to work within silverstripe
        die;
    }
}
