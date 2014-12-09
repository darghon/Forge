<?php
namespace Forge;

class LogEntry
{

    private $type = "";
    private $message = "";

    public function __construct($type = "", $message = "")
    {
        $this->type = $type;
        $this->message = $message;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($mess)
    {
        $this->message = $mess;
    }

    public function __toString()
    {
        return $this->type . " | " . $this->message;
    }

    public function __destruct()
    {
        unset($this->type, $this->message);
    }

}