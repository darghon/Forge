<?php
namespace Core;

abstract class Singleton{

    /**
     * @var Singleton $singleton_instance
     */
    protected static $_instance = null;

    /**
     * @return Singleton
     */
    public static function getInstance(){
        if(self::$_instance === null) self::_createInstance();
        return self::$_instance;
    }

    /**
     * Construct Singleton Instance
     */
    protected static function _createInstance(){
        $caller = function_exists('get_called_class') ? get_called_class() : Tools::getCaller();
        self::$_instance = new $caller();
    }

}