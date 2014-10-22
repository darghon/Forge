<?php
namespace Forge;

/**
 * Public class that does the basic configuration when starting a project.
 * This class will load all defined stages, and make sure that the right output is generated.
 */
abstract class ForgeConfigurator
{

    /**
     * Private final construction because this class may never be constructed.
     */
    private final function __construct()
    {
    }

    /**
     * Setup the initial configuration, make sure the framework gets started, and register all needed paths.
     * @param String $environment
     */
    public static function configure($environment = null)
    {
        $fw_dir = realpath(dirname(__FILE__));
        //static require list
        require($fw_dir . "/forge.class.php");
        require($fw_dir . "/config.class.php");
        require($fw_dir . "/cache.class.php");
        require($fw_dir . "/yaml.class.php");

        //register default paths
        Config::registerPaths();
        if ($environment !== null) Forge::setEnvironment($environment);

        //Start Session
        Session::start();
    }

    public static function deploy()
    {
        $stages = new StageHandler(Config::get('stages'));
        return $stages->deploy();
    }

    public static function deployTask($environment = null, $arguments = array())
    {
        //load task
        unset($environment);
        $task = isset($arguments[1]) ? $arguments[1] : null;
        if ($task !== null) {
            if (file_exists(Config::path("lib") . '/' . $task . ".task.php")) {
                include_once Config::path("lib") . '/' . $task . ".task.php";
            } else {
                echo "Requested task does not exist." . PHP_EOL;
            }
        }
    }
}
