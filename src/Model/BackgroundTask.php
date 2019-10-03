<?php

namespace TractorCow\WebConsole\Model;

use BadMethodCallException;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Core\Path;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * Represents a task that runs in the background
 *
 * @property string $Status
 * @property string $Command
 * @property string $Path
 * @property string $Output
 * @property int    $ExitCode
 * @property int    $StartedByID
 * @method Member StartedBy()
 */
class BackgroundTask extends DataObject
{
    private static $table_name = 'WebConsole_BackgroundTask';

    const READY = 'Ready';

    const STARTED = 'Started';

    const FINISHED = 'Finished';

    private static $db = [
        'Status'   => "Enum('Ready,Started,Finished','Ready')",
        'Command'  => 'Text', // Command
        'Path'     => 'Text', // CWD
        'Output'   => 'Text', // Output
        'ExitCode' => 'Int', // Response integer (1 is success)
    ];

    private static $has_one = [
        'StartedBy' => Member::class,
    ];

    private static $defaults = [
        'Status' => 'Ready',
    ];

    /**
     * Get path to output file.
     *
     * Note: This file is deleted once the task is complete
     *
     * @return string
     */
    public function getOutputPath()
    {
        if (!$this->ID) {
            return null;
        }

        // Ensure standard folder exists
        $folder = Path::join(ASSETS_PATH, '.protected/_consoletasks');
        Filesystem::makeFolder($folder);

        // Background task writes to filesystem folder
        $filename = 'output_' . $this->ID . '.txt';
        return Path::join(ASSETS_PATH, '.protected/_consoletasks', $filename);
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $output = $this->getField('Output');
        if ($output) {
            return $output;
        }

        $path = $this->getOutputPath();
        if ($path && file_exists($path)) {
            return file_get_contents($path);
        }
    }

    /**
     * Mark this task as complete
     *
     * @param int $code Execution code
     * @throws ValidationException
     */
    public function complete($code)
    {
        // Record exit status
        $this->ExitCode = $code;
        $this->Status = self::FINISHED;

        // Migrate file content to output field, then delete the file path
        $path = $this->getOutputPath();
        if (file_exists($path)) {
            $this->Output = file_get_contents($path);
            $this->write();

            // Unlink after write
            unlink($path);
            return;
        }

        $this->write();
    }
}
