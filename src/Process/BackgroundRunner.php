<?php

namespace TractorCow\WebConsole\Process;

use TractorCow\WebConsole\Model\BackgroundTask;

/**
 * Triggers background tasks
 */
interface BackgroundRunner
{
    /**
     * @param string $command Command to run
     * @param string $path CWD to run this in
     * @return BackgroundTask The task generated
     */
    public function run($command, $path);
}
