<?php

namespace TractorCow\WebConsole\Process;

use Exception;
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
     * @throws Exception
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
        $runnerCommand = sprintf(
            "%s %s dev/tasks/RunWebconsoleTask task=%d &> /dev/null &",
            PHP_BINARY,
            escapeshellarg($cliScriptPath),
            $task->ID
        );

        // Create background task and run
        $descriptors = array(
            0 => array('pipe', 'r'), // STDIN
            1 => array('pipe', 'w'), // STDOUT
            2 => array('pipe', 'w')  // STDERR
        );
        $envs = array_merge($_SERVER, $_ENV);

        // Execute and close
        $process = proc_open(
            $runnerCommand,
            $descriptors,
            $pipes,
            BASE_PATH,
            $envs
        );
        if (!is_resource($process)) {
            throw new Exception("Could not run task");
        }
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $task;
    }
}
