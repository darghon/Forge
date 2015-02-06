<?php
namespace Forge\Builder;

use Forge\baseGenerator;
use Forge\Config;
use Forge\TableDefinition;
use Forge\TemplateHandler;
use Forge\Tools;

/**
 * Forge Finder Builder
 * --------------------
 * This class generates the base en custom finder class based on a defined object from the yml configuration.
 * It automatically detects links between objects and tries to add as many helper function for selection based on
 * foreign keys as it can detect.
 *
 * @author Gerry Van Bael
 * @package Forge\Builder
 */
class Finder extends baseGenerator
{
    /** @var TableDefinition */
    protected $_tableDefinition;
    /** @var string */
    protected $_location;
    /** @var string */
    protected $_objectTemplates;

    public function __construct($args = [])
    {
        list($this->_tableDefinition) = $args + [null];
        $this->_location = Config::path('objects').DIRECTORY_SEPARATOR;
        $this->_objectTemplates = $this->_getTemplatesByType('objects/finder');
    }

    public function generate()
    {
        foreach ($this->_objectTemplates as $path => $targetPath) {
            $contents = file_get_contents(Config::path('forge') . '/templates/' . $path);
            $template = new TemplateHandler($contents);
            $template->setTemplateVariables($this->_createTokenMap());

            $template->generateTemplate();
            $template->writeFile($this->_location . $targetPath, strpos($targetPath, 'base{object}') > -1 ? true : false);

        }
    }

    protected function _createTokenMap()
    {
        $oneToOne = [];
        $oneToMany = [];
        foreach ($this->_tableDefinition->getLinks() as $link) {
            if (substr($link->getToObject(), -2) != '[]') {
                $oneToOne[] = [
                    'raw_local_key' => $link->getLocalForeignKey(),
                    'local_key'     => Tools::strtocamelcase($link->getLocalForeignKey(), true),
                    'object'        => $link->getToObject(),
                    'raw_target_key'=> $link->getTargetForeignKey()
                ];
            }
            else {
                $oneToMany[] = [
                    'raw_local_key' => $link->getLocalForeignKey(),
                    'local_key' => Tools::strtocamelcase($link->getLocalForeignKey(),true),
                    'object' => substr($link->getToObject(), 0, -2),
                    'raw_target_key'=> $link->getTargetForeignKey()
                ];
            }
        }

        $extends = $this->_tableDefinition->getExtends();
        $implements = $this->_tableDefinition->getImplements();

        return [
            'object' => $this->_tableDefinition->getTableName(),
            'oneToOne'  => $oneToOne,
            'oneToMany' => $oneToMany,
            'extends'    => $extends["Finder"],
            'implements' => !empty($implements["Finder"]) ? 'implements ' . implode(', ', $implements['Finder']) : ''
        ];
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function __destroy()
    {
        unset($this->_tableDefinition, $this->_objectTemplates, $this->_location);
    }
}