<?php

namespace Tasks;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\View\HTML;
use TractorCow\WebConsole\Model\BackgroundTask;

class RunWebConsoleTask extends BuildTask
{
    private static $segment = 'RunWebConsoleTask';

    protected $title = 'Processes a background webconsole task';

    protected $description = 'Processes a background webconsole task. This should not be run directly.';

    /**
     * @param \SilverStripe\Control\HTTPRequest $request
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function run($request)
    {
        $taskID = $request->getVar('task');
        if (!$taskID) {
            $this->message("No task ID");
            return;
        }

        /** @var BackgroundTask $task */
        $task = BackgroundTask::get()->byID($taskID);
        if (!$task) {
            $this->message('Invalid task for this ID');
            return;
        }

        if ($task->Status !== 'Ready') {
            $this->message('This task is not ready to be started');
            return;
        }

        // Mark task as started
        $task->start();

        // Create path to standard output
        $logto = $task->getOutputPath();
        $command = $task->Command;
        $fullCommand = $command . ' > ' . escapeshellarg($logto) . ' 2>&1';

        // @todo Move to symfony/process

        // Create background task and run
        $descriptors = array(
            0 => array('pipe', 'r'), // STDIN
            1 => array('pipe', 'w'), // STDOUT
            2 => array('pipe', 'w')  // STDERR
        );

        $envs = array_filter(array_merge($_SERVER, $_ENV), function ($item) {
            return !is_array($item);
        });

        $process = proc_open(
            $fullCommand,
            $descriptors,
            $pipes,
            $task->Path,
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

        // All pipes must be closed before "proc_close"
        $code = proc_close($process);
        $task->complete($code);

        if ($code) {
            $this->message('Task completed with error');
        } else {
            $this->message('Task completed successfully');
        }
    }

    /**
     * Log message to output
     *
     * @param $string
     */
    protected function message($string)
    {
        if (Director::is_cli()) {
            echo "{$string}\n";
        } else {
            echo HTML::createTag('p', [], Convert::raw2xml($string));
        }
    }
}
