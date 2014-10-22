<?php
namespace Forge;

class Route
{

    public static function curr_app()
    {
        return Forge::Route()->getApplication();
    }

    public static function curr_mod()
    {
        return Forge::Route()->getModule();
    }

    public static function curr_act()
    {
        return Forge::Route()->getAction();
    }

    public static function curr_param()
    {
        return Forge::Route()->getParam();
    }

    public static function curr_url()
    {
        return Forge::Route()->getURL();
    }

    public static function url($link, $param = array())
    {
        return Forge::Route()->url($link, $param);
    }

    public static function url_rule($name, $param = array())
    {
        return Forge::Route()->url_from_rule($name, $param);
    }

    public static function link($text, $link, $param = array(), $linkparam = array())
    {
        return Forge::Route()->link($text, $link, $param, $linkparam);
    }

    public static function redirect($location, $param = array())
    {
        Forge::registerShutdown();
        header("location: " . self::url($location, $param));
        exit();
    }

}