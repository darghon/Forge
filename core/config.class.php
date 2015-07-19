<?php
namespace Forge;

class Config
{
    /** Command line interface */
    const CLI = 'CLI';
    /** Web interface */
    const WEB = 'WEB';

    public static $namespaces = null;
    protected static $_configurations = [];
    protected static $_paths = [];
    protected static $_mode = self::WEB;
    protected static $default_language = null;
    protected static $available_languages = [];
    protected static $_projectName = 'default_project';
    protected static $_loadedAddons = [];


    /**
     * Namespace retrieval for framework paths
     * Used by the autoloader
     */
    public static function getAutoloaderNamespaces()
    {
        if (self::$namespaces === null) self::initiateNamespaces();

        return self::$namespaces;
    }

    /**
     * Initiate namespace definition for application
     */
    protected static function initiateNamespaces()
    {
        $namespaces = self::get('Namespaces');
        if (empty($namespaces)) {
            //Default Namespaces
            $namespaces = ['Global' => ['Forge' => ['ext/forge/*', 'Builder' => 'ext/forge/builder']]];
        }
        if (!isset($namespaces['Global'])) {
            throw new \Exception('No global namespace definition was found.');
        }
        self::parseNamespace('\\', $namespaces['Global'], self::$namespaces);
    }

    /**
     * Retrieves a configuration file and returns the parsed array
     *
     * @param String $name Name of the configuration file (without .yml)
     * @param String $path Location of the configuration file (Default main config folder)
     *
     * @return Array
     * @Todo: Remove lowercase transform
     */
    public static function get($name, $path = null)
    {
        $fullPath = (is_null($path) ? Config::path('root') . '/config/' : $path) . strtolower($name) . '.yml';

        //quickcheck if it's already loaded
        if (isset(self::$_configurations[$fullPath])) {
            return self::$_configurations[$fullPath];
        }

        //cache config files to read them faster
        if (!file_exists($fullPath)) {
            return [];
        }

        self::$_configurations[$fullPath] = YAML::load($fullPath, true);

        return self::$_configurations[$fullPath];
    }

    /**
     * Retrieve the path location that has been registered
     *
     * @param String $name
     *
     * @return String Location
     */
    public static function path($name)
    {
        if (isset(self::$_paths[$name])) return self::$_paths[$name];
        throw new \InvalidArgumentException('Specified path: "' . $name . '" is not defined.');
    }

    /**
     * Parse all defined rules for namespace
     *
     * @param String       $name
     * @param String|Array $rules
     * @param Array        $collection reference
     */
    protected static function parseNamespace($name, $rules, &$collection)
    {
        $parsedRules = [];
        foreach (is_array($rules) ? $rules : [$rules] as $key => $rule) {
            if (!is_numeric($key)) self::parseNamespace($name . $key . '\\', $rule, $collection);
            else {
                if (substr($rule, -2) == '/*') { //detect all subfolders as well
                    $parsedRules = array_merge($parsedRules, self::detectSubFolders(substr($rule, 0, -2)));
                    $rule = substr($rule, 0, -2);
                }
                $parsedRules[] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rule);
            }
        }
        if (isset($collection[$name . $key . '\\']) && is_array($collection[$name . $key . '\\'])) {
            $parsedRules = array_diff($parsedRules, $collection[$name . $key . '\\']);
        }
        $collection[$name] = isset($collection[$name]) ? array_merge($collection[$name], $parsedRules) : $parsedRules;

        $keys = array_keys($collection);
        foreach($keys as $key) {
            if($key === $name) continue; //skip current key
            if (strpos($name,$key) > -1) {
                $collection[$key] = array_diff($collection[$key], $collection[$name]);
            }
            elseif (strpos($key, $name) > -1) {
                $collection[$name] = array_diff($collection[$name], $collection[$key]);
            }
        }
    }

    /**
     * Detect subFolders in specified path recursively
     *
     * @param $path
     *
     * @return array $subFolders
     */
    protected static function & detectSubFolders($path)
    {
        $subFolders = [];
        $fullPath = self::path('root') . DIRECTORY_SEPARATOR . $path;
        $handle = opendir($fullPath);
        while (is_dir($fullPath) && false !== ($file = readdir($handle))) {
            if (substr($file, 0, 1) != '.' && is_dir($fullPath . DIRECTORY_SEPARATOR . $file)) {
                $subFolders[] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path . DIRECTORY_SEPARATOR . $file);
                $subFolders = array_merge($subFolders, self::detectSubFolders($path . DIRECTORY_SEPARATOR . $file));
            }
        }

        return $subFolders;
    }

    /**
     * @param string $root (Optional)
     *                     Todo: Read path configuration from apps config.
     */
    public static function registerPaths($root = null)
    {
        $root = realpath($root !== null ? $root : realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
        $public = $root . DIRECTORY_SEPARATOR . "public";
        $shared = $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "shared";

        self::registerPath("root", $root); //register root

        self::registerPath("applications|app", $root . DIRECTORY_SEPARATOR . "app"); //register application
        self::registerPath("externals|ext|addon", $root . DIRECTORY_SEPARATOR . "ext"); //register application
        self::registerPath("forge|fw", $root . DIRECTORY_SEPARATOR . "ext" . DIRECTORY_SEPARATOR . "forge"); //register framework
        self::registerPath("public|web", $public); //register web root
        self::registerPath("cache", $root . DIRECTORY_SEPARATOR . "cache"); //register cache
        self::registerPath("config", $root . DIRECTORY_SEPARATOR . "config"); //register config

        self::registerPath("tests", $root . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "tests"); //register tests
        self::registerPath("shared", $shared); //register shared resources

        self::registerPath("lib", $shared . DIRECTORY_SEPARATOR . "lib"); //register lib
        self::registerPath("objects", $shared . DIRECTORY_SEPARATOR . "objects"); //register objects


        //self::registerPath("i18n", $root . DIRECTORY_SEPARATOR . "i18n"); //register internationalization
        self::registerPath("img", $public . DIRECTORY_SEPARATOR . "images"); //register image root
        self::registerPath("js", $public . DIRECTORY_SEPARATOR . "js"); //register javascript root
        self::registerPath("css", $public . DIRECTORY_SEPARATOR . "css"); //register css root
        self::registerPath("uploads", $public . DIRECTORY_SEPARATOR . "uploads"); //register uploads root
        self::registerPath("webcache", $public . DIRECTORY_SEPARATOR . "cache"); //register helper

        self::registerPath("sub_dir", (str_replace(["public/index.php", "index.php"], "", $_SERVER["SCRIPT_NAME"]))); //register sub_dir
        if (isset($_SERVER["HTTP_HOST"])) {
            self::registerPath("url", "http://" . $_SERVER["HTTP_HOST"] . self::path("sub_dir")); //register base url

            self::registerPath("webcache_url", "cache/"); //register web cache url
            self::registerPath("img_url", "images/"); //register img url
            self::registerPath("js_url", "js/"); //register js url
            self::registerPath("css_url", "css/"); //register css url
        }

    }

    /**
     * Register a path to the configuration
     *
     * @param String $name
     * @param String $path
     */
    public static function registerPath($name, $path)
    {
        $names = explode('|', $name);
        foreach ($names as $name) self::$_paths[$name] = $path;
    }

    /**
     * Global configuration is applied at the moment the framework is loaded.
     * The global configuration is loaded from the generic settings yml, defined in the config folder.
     */
    public static function ApplyGlobalConfiguration()
    {
        $settings = self::get('settings');

        if (isset($settings['project_name']) || array_key_exists('project_name', $settings))
            self::$_projectName = $settings['project_name'];
        //Adjust Debug
        if (isset($settings['debug']) || array_key_exists('debug', $settings))
            self::applyDebugOptions($settings['debug']);
        //Adjust Framework version
        if ((isset($settings['framework']) || array_key_exists('framework', $settings)) && (isset($settings['framework']['version']) || array_key_exists('version', $settings['framework'])))
            Forge::setVersion($settings['framework']['version']);
        //Adjust Timezone
        if (isset($settings['timezone']) || array_key_exists('timezone', $settings))
            date_default_timezone_set($settings['timezone']);
        else {
            Debug::Notice('Timezone', 'No timezone has been specified in the settings file, default UTC (GMT 0) is used.');
            date_default_timezone_set('UTC');
        }
        //Adjust Cache
        if ((isset($settings['cache']) || array_key_exists('cache', $settings)) && (isset($settings['cache']['enable']) || array_key_exists('enable', $settings['cache']))) {
            $settings['cache']['enable'] == true ? Cache::enable() : Cache::disable();
        } else {
            Cache::disable();
        }
        //Adjust MultiLingual
        if (isset($settings['multilingual']) || array_key_exists('multilingual', $settings))
            self::applyMultiLingual($settings['multilingual']);

        unset($settings);
    }

    private static function applyDebugOptions($settings)
    {
        if (!isset($settings['enabled']) || !array_key_exists('enabled', $settings) || $settings['enabled'] == false)
            return;
        //Start debug mode
        Debug::start();

        if ((isset($settings['register_error']) || array_key_exists('register_error', $settings)) && $settings['register_error'] == true) {
            //set_error_handler("Debug::registerError");
            //register_shutdown_function("Debug::registerCrash");
        }
    }

    /**
     * applyMultiLingual
     * This function initiates the translation handler and registers the handler in forge
     *
     * @param Array $settings
     */
    private static function applyMultiLingual($settings)
    {
        $th = new TranslationHandler($settings);
        Forge::registerTranslationHandler($th);
    }

    public static function loadHelper($helper)
    {
        if (is_array($helper)) {
            foreach ($helper as $help)
                require(self::path("helpers") . '/' . $help . ".class.php");
        } else {
            require(self::path("helpers") . '/' . $helper . ".class.php");
        }
    }

    public static function loadAddon($addon)
    {
        if (is_array($addon)) {
            foreach ($addon as $add)
                self::loadAddon($add);
        } else {
            $config = self::get('addon', self::path('addon') . '/' . $addon . '/config/');
            if (!empty($config) && array_key_exists('Config', $config)) {
                if (array_key_exists('Namespace', $config['Config'])) {
                    $namespace = self::get($config['Config']['Namespace'], self::path('addon') . '/' . $addon . '/config/');
                    self::updateAutoloaderNamespaces($namespace['Global']);
                }
            }
            self::$_loadedAddons[$addon] = $config;
        }
    }

    public static function updateAutoloaderNamespaces(array $namespaces = [])
    {
        if (self::$namespaces === null) self::initiateNamespaces();
        self::parseNamespace('\\', $namespaces, self::$namespaces);
    }

    public static function getMode()
    {
        return self::$_mode;
    }

    public static function setMode($_mode = Self::WEB)
    {
        self::$_mode = $_mode;
    }

    public static function & getConfiguration()
    {
        return Forge::Configuration();
    }

    public static function getAddonConfiguration()
    {
        $settings = [];
        foreach(self::$_loadedAddons as $key => $config) {
            if (isset($config['Config']) && isset($config['Config']['Settings'])) {
                $addonSettings = self::get($config['Config']['Settings'], self::path('addon') . '/' . $key . '/config/');
                $settings = array_merge($settings, [$key => array_merge($addonSettings, $config)]);
            } else {
                $settings = array_merge($settings, [$key => $config]);
            }
        }

        return $settings;
    }

}