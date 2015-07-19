<?php
namespace Forge;

class Application
{

    /**
     * Placeholder for base url
     */
    const BASE_URL = "<![CDATA[BASE_URL_HOLDER]]>\n";
    /**
     * Placeholder for meta data
     */
    const META_DATA = "<![CDATA[META_DATA_HOLDER]]>\n";
    /**
     * Placeholder for title
     */
    const TITLE = "<![CDATA[TITLE_HOLDER]]>\n";
    /**
     * Placeholder for css
     */
    const CSS = "<![CDATA[STYLESHEET_HOLDER]]>\n";
    /**
     * Placeholder for javascript
     */
    const JS = "<![CDATA[JAVASCIPT_HOLDER]]>\n";

    /**
     * Load and display the main request
     *
     * @return bool
     */
    public static function loadMain()
    {
        return Forge::Response()->loadMain();
    }

    /**
     * Load and display a specified component and action
     *
     * @param String $module
     * @param String $action
     * @param Array  $params
     */
    public static function loadComponent($module = null, $action = null, $params = [])
    {
        Forge::Response()->loadComponent($module, $action, $params);
    }

    /**
     * Create and place a slot placeholder
     *
     * @param String $name
     */
    public static function defineSlot($name)
    {
        Forge::Response()->defineSlot($name);
    }

    /**
     * Assign a component action to a slot
     *
     * @param String $slot_name
     * @param String $module
     * @param String $action
     * @param Array  $params
     */
    public static function assignSlot($slot_name, $module = null, $action = null, $params = [])
    {
        Forge::Response()->assignSlot($slot_name, $module, $action, $params);
    }

    /**
     * Assign a value to a defined slot
     *
     * @param String $slot_name
     * @param String $value
     */
    public static function assignSlotValue($slot_name, $value)
    {
        Forge::Response()->assignSlotValue($slot_name, $value);
    }

    /**
     * Place a custom placeholder
     *
     * @param String $type
     *
     * @return String
     */
    public static function placeHolder($type)
    {
        return sprintf('<![CDATA[%s]]>', $type);
    }

}