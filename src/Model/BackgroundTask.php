<?php

namespace TractorCow\WebConsole\Model;

use BadMethodCallException;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Core\Path;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * Represents a task that runs in the background
 *
 * @property string $Status
 * @property string $Command
 * @property string $Path
 * @property string $Output
 * @property int    $ExitCode
 * @property int    $StartedByID
 * @property string $Started
 * @property string $Finished
 * @method Member StartedBy()
 */
class BackgroundTask extends DataObject
{
    private static $table_name = 'WebConsole_BackgroundTask';

    const READY = 'Ready';

    const STARTED = 'Started';

    const FINISHED = 'Finished';

    private static $default_sort = '"WebConsole_BackgroundTask"."ID" DESC';

    private static $db = [
        'Status'   => "Enum('Ready,Started,Finished','Ready')",
        'Command'  => 'Text', // Command
        'Path'     => 'Text', // CWD
        'Output'   => 'Text', // Output
        'Started'  => 'DBDatetime',
        'Finished' => 'DBDatetime',
        'ExitCode' => 'Int', // Response integer (0 is success)
    ];

    private static $has_one = [
        'StartedBy' => Member::class,
    ];

    private static $summary_fields = [
        'Command'    => 'Command',
        'Created'    => 'Created',
        'NiceStatus' => 'Status',
        'Duration'   => 'Duration (h:m:s)'
    ];

    private static $defaults = [
        'Status' => 'Ready',
    ];

    public function getTitle()
    {
        return $this->Command;
    }

    public function getNiceStatus()
    {
        if ($this->Status === self::FINISHED) {
            return $this->ExitCode ? 'Finished (error)' : 'Finished (success)';
        }

        return $this->Status;
    }

    public function getDuration()
    {
        if ($this->Status !== self::FINISHED) {
            return null;
        }

        $seconds = $this->dbObject('Finished')->getTimestamp()
            - $this->dbObject('Started')->getTimestamp();
        if (!$seconds) {
            return null;
        }

        // Lazy time calc
        $secs = $seconds % 60;
        $minutes = ($seconds - $secs) / 60;
        $mins = $minutes % 60;
        $hours = ($minutes - $mins) / 60;

        // Relative time
        return "{$hours}:{$mins}:{$secs}";
    }

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
     * Start a task
     *
     * @throws ValidationException
     */
    public function start()
    {
        if ($this->Status !== self::READY) {
            throw new BadMethodCallException('Cannot start a task unless it is Ready');
        }

        $this->Started = DBDatetime::now()->getValue();
        $this->Status = self::STARTED;
        $this->write();
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
        $this->Finished = DBDatetime::now()->getValue();
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

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return Permission::checkMember($member, 'ADMIN');
    }
}
