<?php
namespace Forge\Builder;

use Forge\baseGenerator;
use Forge\Config;
use Forge\Tools;
use Forge\Translator;

/**
 * Class Application
 * -----------------
 * Builder class that creates or completes a given application by name.
 * It will create all (missing) files for the default structure of a new application
 *
 * @package Forge\Builder
 * @author  Gerry Van Bael
 */
class Application extends baseGenerator
{
    use Translator;
    /** @var null|string */
    protected $app_name = null;
    protected $_location = null;

    /**
     * Public constructor that receives array of parameters, this class requires 1 argument,
     * which will be used as the application name.
     *
     * @param array $args empty
     *
     * @throws \Exception
     */
    public function __construct($args = [])
    {
        if (!isset($args[0]) || $args[0] == '') {
            throw new \Exception($this->__('No application name was passed.'));
        }
        $this->app_name = Tools::slugify($args[0]);
        $this->_location = Config::path('app') . '/' . $this->app_name . '/';
    }

    /**
     * Public generate action. This method performs all actions required to build the wanted structure and files
     *
     * @return boolean $result
     */
    public function generate()
    {
        try {
            if (file_exists(Config::path("app") . "/" . $this->app_name)) {
                print($this->__('Application has already been build, checking for missing files.') . PHP_EOL);
            }
            $this->generateFolderStructure();
            $this->generateDefaultFiles();

            return true;
        } catch (\Exception $error) {
            print($error->getMessage());

            return false;
        }
    }

    /**
     * Public function that checks the needed folder structure, and creates any missing folders for the application
     */
    public function generateFolderStructure()
    {
        print('Creating folder structure');
        flush();
        $this->_createDirectory([
            Config::path("app") . '/' . $this->app_name,
            Config::path("app") . '/' . $this->app_name . '/config',
            Config::path("app") . '/' . $this->app_name . '/modules',
            Config::path("app") . '/' . $this->app_name . '/templates'
        ], '.', 'x');
        print('DONE' . PHP_EOL);
        flush();
    }

    /**
     * Public function that checks what template files need to be created in the application folder
     * This list is then created if any of the files do not exist yet
     */
    public function generateDefaultFiles()
    {
        print('Creating default files');
        flush();
        $templates = $this->_getTemplatesByType('application');
        foreach ($templates as $path => $targetPath) {
            $contents = file_get_contents(Config::path('forge') . '/templates/' . $path);
            $template = new \Forge\TemplateHandler($contents);
            $template->setTemplateVariables($this->_createTokenMap());

            $template->generateTemplate();
            $template->writeFile($this->_location . $targetPath);
        }
        print('DONE' . PHP_EOL);
    }

    public function __destroy()
    {
        unset($this);
    }

    protected function _createTokenMap()
    {
        return [
            'app_name' => $this->app_name
        ];
    }
}
