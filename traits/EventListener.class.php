<?php
namespace Forge;

trait EventListener
{

    protected $_eventBuffer = [];
    protected $_registered = false;

    public function raiseEvent(IEvent $raisedEvent)
    {
        //register this class to handleEventBuffer
        if (!$this->_registered) $this->_registerEventListener();
        $this->_eventBuffer[] = $raisedEvent;
    }

    protected function _registerEventListener()
    {
        Forge::$_eventCollection[get_class($this)] = $this;
        $this->_registered = true;
    }

    public function handleEventBuffer()
    {
        foreach ($this->_eventBuffer as $event) $event->raiseEvent();

        $this->_eventBuffer = [];
    }


}