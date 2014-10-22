<?php
namespace Forge\Builder;

class Module extends \Forge\baseGenerator
{

    protected $app_name = null;
    protected $mod_name = null;
    protected $actions = array();

    public function __construct($args = array())
    {
        if (count($args) < 2) {
            throw new \Exception('Module generator expects at least 2 parameters, ' . count($args) . ' were given. Parameters needed: app_name, mod_name , [action], [action] ,...');
        }
        $tmp = array_splice($args, 0, 1);
        $this->app_name = \Forge\Tools::slugify($tmp[0]);
        $tmp = array_splice($args, 0, 1);
        $this->mod_name = \Forge\Tools::slugify($tmp[0]);
        $this->actions = $args;
        $this->actions[] = 'index';
        $this->actions = array_unique($this->actions); //make sure each action is only registered once
    }

    public function setApplication($app)
    {
        $this->app_name = $app;
    }

    public function getApplication()
    {
        return $this->app_name;
    }

    public function setModuleName($module_name)
    {
        $this->mod_name = $module_name;
    }

    public function getModuleName()
    {
        return $this->mod_name;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Public generate action. This method performs all actions required to build the wanted files
     * @return boolean $result;
     */
    public function generate()
    {
        if (!file_exists(\Forge\Config::path("app") . "/" . $this->app_name . "/" . $this->mod_name)) {
            //folder does not exists, so ok to proceed
            $this->generateFolderStructure();
            $this->generateDefaultFiles();
            return true;
        } else {
            print('Module has already been build.' . PHP_EOL);
            return false;
        }
    }

    public function generateFolderStructure()
    {
        print('Creating folder structure');
        flush();
        $this->_createDirectory(array(
            \Forge\Config::path("app") . "/" . $this->app_name . "/modules/" . $this->mod_name . "/actions",
            \Forge\Config::path("app") . "/" . $this->app_name . "/modules/" . $this->mod_name . "/templates",
            \Forge\Config::path("app") . "/" . $this->app_name . "/modules/" . $this->mod_name . "/config"
        ), '.', 'x');
        print('DONE' . PHP_EOL);
        flush();
    }

    public function generateDefaultFiles()
    {
        print('Creating default files');
        flush();
        $templates = scandir(\Forge\Config::path('forge') . '/templates');
        foreach ($templates as $template) {
            $file = explode('_', $template);
            if ($file[0] == 'module') {
                array_splice($file, 0, 1); //drop the "module" part of the filename
                $new_file = implode('/', $file);
                $new_file = \Forge\Config::path('app') . '/' . $this->app_name . '/modules/' . $this->mod_name . '/' . substr($new_file, 0, strlen($new_file) - 9);
                $contents = file_get_contents(\Forge\Config::path('forge') . '/templates/' . $template);
                if (substr($file[0], 0, 5) == 'templ') {
                    foreach ($this->actions as $action) {
                        file_put_contents($this->replaceTokens($new_file, array('action' => $action)), $this->replaceTokens($contents, array('action' => $action)));
                        print('.');
                        flush();
                    }
                } else {
                    file_put_contents($this->replaceTokens($new_file), $this->replaceTokens($contents));
                    print('.');
                    flush();
                }
            }
        }
        print('DONE' . PHP_EOL);
    }

    protected function transformActions()
    {
        $actions = PHP_EOL;
        foreach ($this->actions as $action) {
            $actions .= sprintf("\t/**" . PHP_EOL);
            $actions .= sprintf("\t * Add your action description here." . PHP_EOL);
            $actions .= sprintf("\t */" . PHP_EOL);
            $actions .= sprintf("\tpublic function %sAction(){" . PHP_EOL, $action);
            $actions .= sprintf("\t\t//insert code here." . PHP_EOL);
            $actions .= sprintf("\t}" . PHP_EOL);
            $actions .= PHP_EOL;
        }
        return $actions;
    }

    public function __destroy()
    {
        unset($this->name, $this->fields, $this->location);
        parent::__destroy();
    }

}