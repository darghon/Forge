<?php
namespace Forge;

class ActionQueue
{
    public $content = '';
    private $module = null;
    private $action = null;
    private $params = null;
    private $placeholder = null;

    public function __construct($mod, $act, $params)
    {
        $this->module = $mod;
        $this->action = $act;
        $this->params = $params;
        $this->createHolder();
    }

    private function createHolder()
    {
        $this->placeholder = '[HOLDER[' . $this->module . '|' . $this->action . '|' . time() . '] FOR COMPONENT]';
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setModule($mod)
    {
        $this->module = $mod;
        $this->createHolder();
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
        $this->createHolder();
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function placeHolder()
    {
        echo $this->placeholder;
    }

}