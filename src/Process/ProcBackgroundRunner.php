<?php

namespace TractorCow\WebConsole\Process;

use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use TractorCow\WebConsole\Model\BackgroundTask;

class ProcBackgroundRunner implements BackgroundRunner
{
    /**
     * @param string $command
     * @param string $path
     * @return BackgroundTask
     * @throws ValidationException
     */
    public function run($command, $path)
    {
        // Build task model
        $task = BackgroundTask::create();
        $task->Command = $command;
        $task->Path = $path;
        if (Security::getCurrentUser()) {
            $task->StartedByID = Security::getCurrentUser()->ID;
        }
        $task->write();

        // Build inner command to offload responsibility to the dev task
        $cliScriptPath = BASE_PATH . '/vendor/silverstripe/framework/cli-script.php';
        $shellCMD = sprintf(
            "%s %s dev/tasks/RunWebconsoleTask task=%d",
            PHP_BINARY,
            escapeshellarg($cliScriptPath),
            $task->ID
        );

        // Execute outer command
        exec(sprintf(
            "%s &> /dev/null &",
            $shellCMD
        ));

        return $task;
    }
}
