<?php

namespace TractorCow\WebConsole\Process;

use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use Symfony\Component\Process\Process;
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
     * @param BackgroundTask $task
     * @return int
     */
    protected function startTask($task)
    {
        // Build inner command to offload responsibility to the dev task
        $cliScriptPath = BASE_PATH . '/vendor/silverstripe/framework/cli-script.php';

        // Use '&' to run in background
        $runnerCommand = sprintf(
            "%s %s dev/tasks/RunWebConsoleTask task=%d &",
            $this->getPHPBinary(),
            escapeshellarg($cliScriptPath),
            $task->ID
        );

        // Disable output so the background task runs
        $process = new Process($runnerCommand, BASE_PATH);
        $process->disableOutput();
        $result = $process->run();

        return $result;
    }
}
