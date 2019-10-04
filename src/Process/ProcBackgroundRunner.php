<?php

namespace TractorCow\WebConsole\Process;

use Exception;
use SilverStripe\Core\Environment;
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
        $this->startTask($task);

        return $task;
    }

    /**
     * @return string
     */
    protected function getPHPBinary()
    {
        // Note: fulltextsearch module also uses this bin path
        return Environment::getEnv('SS_PHP_BIN') ?: 'php';
    }

    /**
     * @param $task
     * @param $pipes
     * @throws Exception
     * @return int
     */
    protected function startTask($task)
    {
        // Build inner command to offload responsibility to the dev task
        $cliScriptPath = BASE_PATH . '/vendor/silverstripe/framework/cli-script.php';
        $runnerCommand = sprintf(
            "%s %s dev/tasks/RunWebConsoleTask task=%d &",
            $this->getPHPBinary(),
            escapeshellarg($cliScriptPath),
            $task->ID
        );

        // @todo - Move to symfony/process

        // Create background task and run
        $descriptors = array(
            0 => array('pipe', 'r'), // STDIN
            1 => array('pipe', 'w'), // STDOUT
            2 => array('pipe', 'w')  // STDERR
        );
        $envs = array_filter(array_merge($_SERVER, $_ENV), function($item) {
            return !is_array($item);
        });

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

        // Don't ask why, but stream_get_contents is important here
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process);
    }
}
