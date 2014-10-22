<?php
namespace Forge;

interface IEvent
{

    /**
     * Initiate the event class with a context (object that triggered the event)
     * @param null|mixed $context
     */
    public function __construct($context = null);

    /**
     * Run the event in question
     * @return bool $success
     */
    public function raiseEvent();

}