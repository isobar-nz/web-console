<?php

class ConsoleController extends Controller
{
    private static $url_segment = 'webconsole';

    private static $allowed_actions = [
        'callback',
    ];

    public function callback()
    {
        if (!Permission::check('ADMIN')) {
            Security::permissionFailure($this, 'You must be admin to use this module');
        }

        // Permission approved
        global $NO_LOGIN;
        $NO_LOGIN = true;

        global $HOME_DIRECTORY;
        $HOME_DIRECTORY = BASE_PATH;

        // Handle passthrough
        include __DIR__ . '/../../webconsole/src/webconsole.includes.php';
        include __DIR__ . '/../../webconsole/src/webconsole.main.php';
    }
}
