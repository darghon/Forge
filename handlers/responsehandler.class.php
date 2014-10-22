<?php
namespace Forge;

/**
 * Response Handler builds the the page, (caches it) and returns the page to the requestor.
 *
 * @author gerry.vanbael
 */
class ResponseHandler implements IStage
{

    use Translator;

    protected $content_buffer = null;
    protected $main_buffer = null;
    protected $slots = array();
    protected $app = null;
    protected $mod = null;
    protected $act = null;
    /**
     * Object reference to the global configuration handler
     * @var ConfigurationHandler
     */
    protected $configuration = null;

    public function __construct()
    {
    }

    /**
     * Retrieve the configuration rendered for the request
     */
    public function initialize()
    {
        $this->configuration = &Config::getConfiguration();
    }

    public function deploy()
    {
        //check if a hook is defined
        if (Request::getParameter('_hook', null) != null) {
            if (Request::getParameter('_hook', null) == 'service') {
                //render a service
                $this->deployService($this->configuration->getApp(), $this->configuration->getMod(), $this->configuration->getAct(), $this->configuration->getVariables);
            } elseif (Request::getParameter('_hook', null) == 'component') {
                //render a component
                $this->renderComponent($this->configuration->getApp(), $this->configuration->getMod(), $this->configuration->getAct(), $this->configuration->getVariables);
                $this->content_buffer = ob_get_clean();
            }
        } else {
            //check if full page cache is enabled, and if cached pages is still valid, ajax requests are never cached
            if (!Request::isAjaxCall() && Cache::fpc() !== null && false !== ($content = Cache::load_fpc(Tools::encrypt('fpc_' . $this->configuration->getApp() . '_' . $this->configuration->getMod() . '_' . $this->configuration->getAct()) . '.php', false))) {
                echo $content;
                if (Debug::enabled()) Debug::stop();
                unset($content);
                return true;
            }

            //We start the buffer, and load the template
            ob_start();

            if (Debug::enabled()) Debug::addTimer('Build');
            //load main first
            $this->renderMain();
            if (!Request::isAjaxCall()) {
                //no ajax call
                require(Config::path("app") . DIRECTORY_SEPARATOR . $this->configuration->getApp() . "/templates/" . $this->configuration->getTemplate());
                $this->ob_dump_to($this->content_buffer);

                //replace all placeholders with components
                foreach ($this->slots as &$item) {
                    $this->renderComponent($this->configuration->getApp(), $item->getModule(), $item->getAction(), $item->getParams());
                    $this->content_buffer = str_replace($item->getPlaceholder(), ob_get_clean(), $this->content_buffer);
                }

                $this->ob_dump_to($this->content_buffer, true);

                //Adjust head information and apply slots
                $this->adjustHead();
                $this->applySlots();
            } else {
                //overwrite content with main
                $this->content_buffer = $this->main_buffer;
                ob_end_clean();
            }

            if (Debug::enabled()) {
                Debug::stopTimer('Build');
                Debug::stop();
                $this->applyDebug();
                //Debug::registerLongLoad();
            }

            if (Cache::fpc() !== null) { //save full page cache
                Cache::save_fpc(Tools::encrypt('fpc_' . $this->configuration->getApp() . '_' . $this->configuration->getMod() . '_' . $this->configuration->getAct()) . '.php', $this->content_buffer);
            }
        }

        //echo content
        echo $this->content_buffer;
        return true;
    }

    public function loadMain()
    {
        echo $this->main_buffer;
    }

    public function loadComponent($mod, $act, $param)
    {
        $this->renderComponent($this->configuration->getApp(), $mod, $act, $param);
    }

    public function defineSlot($slot_name)
    {
        $this->slots[$slot_name] = new Slot($slot_name);
        //set placeholder
        echo $this->slots[$slot_name]->getPlaceholder();
    }

    public function assignSlot($slot_name, $mod, $act, $param = array())
    {
        $slot_specs["module"] = $mod;
        $slot_specs["action"] = $act;
        $slot_specs["param"] = $param;

        $this->assigned_slots[$slot_name] = $slot_specs;
    }

    public function assignSlotValue($slot_name, $value)
    {
        $this->assigned_slots[$slot_name] = $value;
    }

    protected function renderMain()
    {
        $this->ob_dump_to($this->content_buffer);
        $this->render($this->configuration->getApp(), $this->configuration->getMod(), $this->configuration->getAct(), $this->configuration->getVariables());
        $this->ob_dump_to($this->main_buffer);
    }

    protected function renderComponent($app, $mod, $act, $param)
    {
        Debug::addTimer($mod . '/' . $act);
        //set class name to call
        $action_class = $mod . "Components";
        //require needed file (once)
        if (file_exists(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/components.class.php")) {
            require_once(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/components.class.php");
            $action = new $action_class();
            if (method_exists($action, "preActions")) $action->preActions();
            if (method_exists($action, $act)) {
                Debug::addTimer($mod . '-component');
                $action->{$act}($param);
                Debug::stopTimer($mod . '-component');
            }
            if (method_exists($action, "postActions")) $action->postActions();

            Debug::addTimer($mod . '-template');
            $action->loadTemplate($this->configuration->getApp(), $mod, $act);
            Debug::stopTimer($mod . '-template');
        } else {
            echo "Component not found.";
        }
        Debug::stopTimer($mod . '/' . $act);
    }

    private function render($app, $mod, $act, $param)
    {
        //set class name to call
        if (Debug::enabled()) Debug::addTimer($mod . '/' . $act);

        $action_class = $mod . "Actions";
        $action_method = $act . "Action";
        //require needed file (once)
        if (file_exists(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/actions.class.php")) {
            require_once(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/actions.class.php");
            /** @var Actions $action */
            $action = new $action_class();
            if (method_exists($action, "preActions")) $action->preActions();
            if (method_exists($action, $action_method)) {
                if (Debug::enabled()) Debug::addTimer($mod . '-action');
                $action->{$action_method}($param);
                if (Debug::enabled()) Debug::stopTimer($mod . '-action');
            } else {
                ob_end_clean();
                echo '<h1>404 - Page not found!</h1>';
                Tools::dump($this->configuration);
                exit;
            }
            if (method_exists($action, "postActions")) $action->postActions();
            if (Debug::enabled()) Debug::addTimer($mod . '-template');
            $action->loadTemplate($this->configuration->getApp(), $mod, $act);
            if (Debug::enabled()) Debug::stopTimer($mod . '-template');
        } else {
            ob_end_clean();
            echo '<h1>404 - Page not found!</h1>';
            Tools::dump($this->configuration);
            exit;
        }
        if (Debug::enabled()) Debug::stopTimer($mod . '/' . $act);
    }

    private function deployService($app, $mod, $act, $param)
    {
        if (Debug::enabled()) Debug::addTimer($mod . '/' . $act);

        $service_class = $mod . "Service";
        //require needed file (once)
        if (file_exists(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/service.class.php")) {
            require_once(Config::path("app") . "/" . $app . "/modules/" . $mod . "/actions/service.class.php");
            $service = new $service_class();
            if (method_exists($service, "preService")) $service->preService();
            if (method_exists($service, $act)) {
                //make sure json headers are sent
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                //load service
                if (Debug::enabled()) Debug::addTimer($mod . '-service');
                $service->{$act}($param);
                if (Debug::enabled()) Debug::stopTimer($mod . '-service');
            } else {
                ob_end_clean();
                echo '<h1>404 - Service does not exist!</h1>';
                Tools::dump($this->configuration);
                exit;
            }
            if (method_exists($service, "postService")) $service->postService();
        } else {
            ob_end_clean();
            echo '<h1>404 - Service does not exist!</h1>';
            Tools::dump($this->configuration);
            exit;
        }
        if (Debug::enabled()) Debug::stopTimer($mod . '/' . $act);
    }

    protected function ob_dump_to(&$var, $end = false)
    {
        if (!$end) {
            $var .= ob_get_contents();
            ob_clean();
        } else {
            $var .= ob_get_contents();
            ob_end_clean();
        }
    }

    protected function adjustHead()
    {
        $this->renderBase();
        $this->renderMeta();
        $this->renderTitle();
        $this->renderCss();
        $this->renderJs();
    }

    protected function applySlots()
    {
        //execute all assigned slots
        foreach ($this->slots as $name => $slot) {
            if (isset($this->assigned_slots[$name])) {
                //Slot was assigned, filling it up now
                if (is_array($this->assigned_slots[$name])) {
                    $slot_spec = $this->assigned_slots[$name];
                    ob_start();
                    Application::loadComponent($slot_spec["module"], $slot_spec["action"], $slot_spec["param"]);
                    $slot->setContent(ob_get_contents());
                    ob_end_clean();
                } else {
                    $slot->setContent($this->assigned_slots[$name]);
                }
            }
            //replace placeholders with content
            $this->content_buffer = str_replace($slot->getPlaceholder(), $slot->getContent(), $this->content_buffer);
        }
    }

    protected function applyDebug()
    {
        if (strpos($this->content_buffer, '[DEBUG PLACEHOLDER-----[DEBUG]') > -1) {
            $debug = "This page took " . Debug::showTimer('global') . " seconds and " . Forge::Database()->getUseCounter() . " queries (" . Forge::Database()->getRecordCounter() . " records) to generate.<br />";
            $debug .= "Total query time: " . Forge::Database()->getQueryTime() . " seconds.<br />";
            $debug .= "Total pages included: " . Forge::getPageIncludes() . "<br />";
            //$debug .= implode('<br />',Forge::Database()->getSqlCollection());
            $debug .= "Your ip address: " . $_SERVER['REMOTE_ADDR'] . "<br />";

            $debug .= "<br />Timers above 100ms:<br />";
            $timers = Debug::getAlarmingTimers();
            foreach ($timers as $timer) {
                $debug .= $timer->getName() . ': ' . $timer->getDuration(5) . '<br />';
            }
            $debug .= "<br />Debug notices:<br />";
            $logs = Debug::getAllLogs();
            foreach ($logs as $log) {
                $debug .= $log->getType() . ': ' . $log->getMessage() . '<br />';
            }
            //replace placeholder with content
            $this->content_buffer = str_replace('[DEBUG PLACEHOLDER-----[DEBUG]', $debug, $this->content_buffer);
        }
    }

    /**
     * Protected function to render the base tag for head
     */
    protected function renderBase()
    {
        $base = sprintf('<base href="%s" />', $this->configuration->getBase());
        $this->content_buffer = str_replace(Application::BASE_URL, $base . PHP_EOL, $this->content_buffer);
    }

    protected function renderMeta()
    {
        $meta = array();
        $metas = $this->configuration->getMeta();
        foreach ($metas as $name => $value) {
            if ($name == "Content-Type") { //special
                $meta[] = sprintf('<meta http-equiv="Content-Type" content="%s" />', $value);
            } else {
                $meta[] = sprintf('<meta name="%s" content="%s" />', $name, ((is_array($value)) ? implode(" ", $value) : $value));
            }
        }
        $this->content_buffer = str_replace(Application::META_DATA, implode("\n", $meta) . PHP_EOL, $this->content_buffer);
    }

    protected function renderTitle()
    {
        $title = sprintf('<title>%s</title>', $this->configuration->getTitle());
        $this->content_buffer = str_replace(Application::TITLE, $title . PHP_EOL, $this->content_buffer);
    }

    protected function renderCss()
    {
        $css = array();
        if (Cache::enabled() && file_exists(Config::path('css') . '/' . Cache::getCss($this->configuration->getCss()))) {
            $css[] = sprintf('<link href="%s" rel="stylesheet" type="text/css" />', Cache::getCss($this->configuration->getCss()));
        } else {
            $css_list = $this->configuration->getCss();
            foreach ($css_list as $value) {
                if (!file_exists(Config::path('css') . '/' . $value)) {
                    Debug::Error('FILE NOT FOUND', sprintf('Tried to include "%s" but file does not exist!', Config::path('css') . '/' . $value));
                    continue;
                }
                $css[] = sprintf('<link href="%s" rel="stylesheet" type="text/css" />', Config::path('css_url') . $value);
            }
        }
        $this->content_buffer = str_replace(Application::CSS, implode("\n", $css) . PHP_EOL, $this->content_buffer);
    }

    protected function renderJs()
    {
        $js = array();
        if (Cache::enabled() && file_exists(Config::path('js') . '/' . Cache::getJs($this->configuration->getJs()))) {
            $js[] = sprintf('<script language="javascript" type="text/javascript" src="%s"></script>', Cache::getCss($this->configuration->getJs()));
        } else {
            $js_list = $this->configuration->getJs();
            foreach ($js_list as $value) {
                if (!file_exists(Config::path('js') . '/' . $value)) {
                    Debug::Error('FILE NOT FOUND', sprintf('Tried to include "%s" but file does not exist!', Config::path('js') . '/' . $value));
                    continue;
                }
                $js[] = sprintf('<script language="javascript" type="text/javascript" src="%s"></script>', Config::path('js_url') . $value);
            }
        }
        $this->content_buffer = str_replace(Application::JS, implode("\n", $js) . PHP_EOL, $this->content_buffer);
    }

    public function __destroy()
    {
        foreach ($this as $key => $value) unset($this->$key);
        unset($this);
    }
}

?>
