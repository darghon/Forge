<?php
namespace Forge\Builder;

use Forge\Config;

/**
 * Generator that builds the folder structure of the framework.
 * When this object is called to generate, it will check if the
 * project configuration already exists, if so, the project is
 * considered "generated". if not, it will attempt to create all
 * neccesary folders and default configuration files
 *
 * @author Darghon
 */
class Project extends \Forge\baseGenerator
{

    protected $_location;

    /**
     * Public constructor that receives an in this case empty parameter array.
     *
     * @param array empty
     */
    public function __construct($args = [])
    {
    }

    /**
     * Public generate action. This method performs all actions required to build the wanted files
     *
     * @return boolean $result;
     */
    public function generate()
    {
        if (!file_exists(\Forge\Config::path("config") . "/projectConfigurator.class.php")) {
            //folder does not exists, so ok to proceed
            $this->generateFolderStructure();
            $this->generateDefaultFiles();

            return true;
        } else {
            print('Project has already been build.' . PHP_EOL);

            return false;
        }
    }

    public function generateFolderStructure()
    {
        /** Todo: Refactor all paths to create a new and correct structure */
        print('Creating folder structure');
        flush();
        $this->_createDirectory([
            \Forge\Config::path("app"),
            \Forge\Config::path("config") . '/database',
            \Forge\Config::path("lib"),
            \Forge\Config::path("objects") . '/business/base',
            \Forge\Config::path("objects") . '/data/base',
            \Forge\Config::path("objects") . '/finders/base',
            \Forge\Config::path("public") . '/css',
            \Forge\Config::path("public") . '/js',
            \Forge\Config::path("public") . '/images',
            \Forge\Config::path("public") . '/plugins',
            \Forge\Config::path("public") . '/uploads',
        ], '.', 'x');
        print('DONE' . PHP_EOL);
        flush();
    }

    public function generateDefaultFiles()
    {
        print('Creating default files');
        flush();
        $templates = $this->_getTemplatesByType();
        foreach ($templates as $path => $targetPath) {
            $file = explode('_', $path);
            if (in_array($file[0],['config','public','lib']) || substr($file[0], 0, 9) == '.htaccess') {
                $this->_location = ($file[0] !== 'lib' ? \Forge\Config::path('root') : Config::path('shared')) . '/';

                $contents = file_get_contents(Config::path('forge') . '/templates/' . $path);
                $template = new \Forge\TemplateHandler($contents);
                $template->generateTemplate();
                $template->writeFile($this->_location . $targetPath, true);
            }
        }
        print('DONE' . PHP_EOL);
    }

    public function __destroy()
    {
        unset($this);
    }
}
