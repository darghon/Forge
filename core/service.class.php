<?php
namespace Forge;

abstract class Service extends Actions
{

    protected $results = array();
    protected $errors = array();
    protected $success = null;

    public function __construct()
    {

    }

    protected function getErrors()
    {
        return $this->errors;
    }

    protected function addError($category, $message)
    {
        if (isset($this->errors[$category])) {
            if (!is_array($this->errors[$category])) {
                $tmp = $this->errors[$category];
                $this->errors[$category] = array($tmp);
            }
            $this->errors[$category][] = $message;
        }
        $this->errors[$category] = $message;
        return $this;
    }

    protected function addResult($variable, $value)
    {
        if (isset($this->results[$variable])) {
            if (!is_array($this->results[$variable])) {
                $tmp = $this->results[$variable];
                $this->results[$variable] = array($tmp);
            }
            $this->results[$variable][] = $value;
        }
        $this->results[$variable] = $value;
        return $this;
    }

    protected function getResults()
    {
        return $this->results;
    }

    protected function success($encoding = 'json')
    {
        $this->success = true;
        return $this;
    }

    protected function fail($encodeing = 'json')
    {
        $this->success = false;
        return $this;
    }

    public function postService()
    {
        printf('%s', json_encode(array(
            'success' => true,
            'results' => $this->results,
            'errors' => $this->errors
        )));
    }

    public function __destruct()
    {
        foreach ($this as $key => $val) unset($this->$key);
        unset($this);
    }
}