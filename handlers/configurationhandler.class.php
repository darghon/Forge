<?php
namespace Forge;

/**
 * Configurationhandler is an instance that handles most of the requested action configuration
 */
class ConfigurationHandler implements IStage
{

    protected $js = [];
    protected $css = [];
    protected $meta = [];
    protected $title = "No Title";
    protected $template = null;
    protected $variables = [];
    protected $app = null;
    protected $mod = null;
    protected $act = null;
    protected $_settings = [];

    public function __construct()
    {
    }

    /**
     * Load all predefined settings from routing.
     */
    public function initialize()
    {
        $this->app = Route::curr_app();
        $this->mod = Route::curr_mod();
        $this->act = Route::curr_act();
    }

    /**
     * Build configuration for request (or load from cache if available).
     */
    public function deploy()
    {
        //check if cache is enabled
        if (!Cache::enabled() || !$this->loadFromCache()) {
            $appLocation = Config::path("app") . DIRECTORY_SEPARATOR . $this->app;
            //load settings for request
            $settings = Config::get("settings");
            $app_config = Config::get("view", $appLocation . "/config/");
            $mod_config = Config::get("view", $appLocation . "/modules/" . $this->mod . "/config/");
            $addon_config = Config::getAddonConfiguration();

            // Load all settings where they may be registered.
            if (isset($settings['global']) || array_key_exists('global', $settings)) $this->parseConfig($settings['global']);
            if (isset($app_config['global']) || array_key_exists('global', $app_config)) $this->parseConfig($app_config['global']);
            if (isset($app_config[$this->mod]) || array_key_exists($this->mod, $app_config)) $this->parseConfig($app_config[$this->mod]);
            if (isset($mod_config['global']) || array_key_exists('global', $mod_config)) $this->parseConfig($mod_config['global']);
            if (isset($mod_config[$this->act]) || array_key_exists($this->act, $mod_config)) $this->parseConfig($mod_config[$this->act]);

            $this->setSettings(array_merge($addon_config, $settings, $app_config, $mod_config));

            unset($settings, $app_config, $mod_config);

            if (Cache::enabled()) $this->cacheConfig(); //cache config is cache is enabled
        }
        //always load additional url params, because they're not entered through configuration, or might overwrite default settings (like pagesize etc)
        if (count(Route::curr_param()) > 0) {
            $params = Route::curr_param();
            foreach ($params as $key => $value) {
                $this->variables[$key] = $value;
            }
        }
    }

    private function loadFromCache()
    {
        $result = Cache::load('modulecache_' . $this->app . '_' . $this->mod . '_' . $this->act . '.php', false);
        if (!$result) return false;
        $this->js = $result['js'];
        $this->css = $result['css'];
        $this->meta = $result['meta'];
        $this->title = $result['title'];
        $this->template = $result['template'];
        $this->variables = $result['variables'];

        return true;
    }

    /**
     * This function parses a configuration array to apply its content.
     *
     * @param array $config
     */
    private function parseConfig($config)
    {
        if (is_array($config)) {
            foreach ($config as $index => $entry) {
                if (empty($entry)) continue;
                switch ($index) {
                    case 'title': //Set Title
                        $this->title = $entry;
                        break;
                    case 'template': //Set Template
                        $this->template = $entry;
                        break;
                    case 'stylesheet': //Set Css
                        $this->setEntry('css', $entry);
                        break;
                    case 'javascript': //Set Js
                        $this->setEntry('js', $entry);
                        break;
                    case 'metas': //Set metas
                        $this->setMeta($entry);
                        break;
                    default: //Else
                        $this->variables[$index] = $entry;
                        break;
                }
            }
        }
    }

    private function setEntry($type, $value)
    {
        if (is_array($value)) {
            foreach ($value as $entry) {
                $this->applyEntry($type, $entry);
            }
        } else {
            $this->applyEntry($type, $value);
        }

        return true;
    }

    private function applyEntry($type, $value)
    {
        if (substr($value, 0, 1) == "-") { //needs to be removed
            if (substr($value, 1, 1) == "*") { //remove all
                $this->$type = [];
            } else { //remove specific
                foreach ($this->$type as $key => $i) {
                    if ($i == $value) {
                        unset($this->$type[$i]);
                    }
                }
            }
        } else {
            array_push($this->$type, $value);
        }
    }

    private function setMeta($metas)
    {
        foreach ($metas as $name => $meta) {
            switch ($name) {
                case 'tags':
                    if (!is_array($meta)) $meta = [$meta];
                    $this->meta[$name] = isset($this->meta[$name]) || array_key_exists($name, $this->meta) ? array_merge($this->meta[$name], $meta) : $meta;
                    break;
                default:
                    $this->meta[$name] = $meta;
                    break;
            }
        }
    }

    private function cacheConfig()
    {
        $toBuff = [];
        $toBuff['js'] = $this->js;
        $toBuff['css'] = $this->css;
        $toBuff['meta'] = $this->meta;
        $toBuff['title'] = $this->title;
        $toBuff['template'] = $this->template;
        $toBuff['variables'] = $this->variables;
        Cache::save('modulecache_' . $this->app . '_' . $this->mod . '_' . $this->act . '.php', serialize($toBuff));
    }

    public function getApp()
    {
        return $this->app;
    }

    public function setApp($app)
    {
        $this->app = $app;
    }

    public function getMod()
    {
        return $this->mod;
    }

    public function setMod($mod)
    {
        $this->mod = $mod;
    }

    public function getAct()
    {
        return $this->act;
    }

    public function setAct($act)
    {
        $this->act = act;
    }

    public function getJs()
    {
        return $this->js;
    }

    public function addJs($js)
    {
        return $this->setEntry('js', $js);
    }

    public function getCss()
    {
        return $this->css;
    }

    public function addCss($css)
    {
        return $this->setEntry('css', $css);
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function addMeta($type, $values)
    {
        $this->setMeta([$type => $values]);
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getBase()
    {
        return Config::path('url');
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function addVariables($key, $value)
    {
        $this->variables[$key] = $value;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->_settings;
    }

    /**
     * @param array $_settings
     *
     * @return $this
     */
    public function setSettings($_settings)
    {
        $this->_settings = $_settings;

        return $this;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return array|string|null
     */
    public function getSetting($key, $default = null)
    {
        $keyList = explode('/',$key);
        $settings = &$this->_settings;
        foreach($keyList as $entry) {
            if (isset($settings[$entry])) $settings = &$settings[$entry];
            else return $default;
        }
        return $settings;
    }

    public function __destroy()
    {
        foreach ($this as $key => $value)
            unset($this->$key);
        unset($this);
    }

}

?>