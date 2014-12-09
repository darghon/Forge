<?php
namespace Forge;

class LogHandler
{

    private $notices = [];
    private $errors = [];
    private $logs = [];

    public function __construct()
    {

    }

    public function addNotice($type, $message)
    {
        $this->notices[] = new LogEntry($type, $message);
    }

    public function addError($type, $message)
    {
        $this->errors[] = new LogEntry($type, $message);
    }

    public function addLog($type, $message)
    {
        $this->logs[] = new LogEntry($type, $message);
    }

    public function & getLogByType($type)
    {
        $result = [];
        foreach ($this->notices as &$entry) {
            if ($entry->getType() == $type) {
                $result[] =& $entry;
            }
        }
        foreach ($this->errors as &$entry) {
            if ($entry->getType() == $type) {
                $result[] =& $entry;
            }
        }
        foreach ($this->logs as &$entry) {
            if ($entry->getType() == $type) {
                $result[] =& $entry;
            }
        }

        return $result;
    }

    public function & getAll()
    {
        $result = [];
        foreach ($this->notices as &$entry) {
            $result[] =& $entry;
        }
        foreach ($this->errors as &$entry) {
            $result[] =& $entry;
        }
        foreach ($this->logs as &$entry) {
            $result[] =& $entry;
        }

        return $result;
    }

    public function __destruct()
    {
        unset($this->notices, $this->errors, $this->logs);
    }

}

?>
