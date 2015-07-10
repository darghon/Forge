<?php
namespace Forge;

interface IBootable
{
    /**
     * Boot method used to start the module
     * @return bool $success
     */
    public static function boot();
}