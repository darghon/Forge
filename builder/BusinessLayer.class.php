<?php
namespace Forge\Builder;

use Forge\baseGenerator;
use Forge\Config;
use Forge\TableDefinition;
use Forge\Tools;

/**
 * Class BusinessLayer
 * -------------------
 * Builder class that creates the base and normal version of the businesslayer object.
 * If the normal version exists, this one is kept. the base version will always be overwritten
 *
 * @package Forge\Builder
 * @author  Gerry Van Bael
 */
class BusinessLayer extends baseGenerator
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
        $this->_objectTemplates = $this->_getTemplatesByType('objects/business');
    }

    public function generate()
    {
        foreach ($this->_objectTemplates as $path => $targetPath) {
            $contents = file_get_contents(Config::path('forge') . '/templates/' . $path);
            $template = new \Forge\TemplateHandler($contents);
            $template->setTemplateVariables($this->_createTokenMap());

            $template->generateTemplate();
            $template->writeFile($this->_location . $targetPath, strpos($targetPath, 'base{object}') > -1 ? true : false);

        }
    }

    protected function _createTokenMap()
    {
        $columns = [];
        foreach ($this->_tableDefinition->getColumns() as $column) {
            $columns[] = [
                'column_name'     => Tools::strtocamelcase($column->getName(), true),
                'column_type'     => $column->getType(),
                'raw_column_name' => Tools::camelcasetostr($column->getName()),
                'raw'             => $column->getName()
            ];
        }
        $links = [];
        foreach ($this->_tableDefinition->getLinks() as $link) {
            $links[] = [
                'link_name'      => Tools::strtocamelcase($link->getLinkName(), true),
                'link_result'    => $link->getToObject(),
                'link_object'    => str_replace('[]', '', $link->getToObject()),
                'link_local_key' => $link->getLocalForeignKey(),
                'raw_link_name'  => Tools::camelcasetostr($link->getLinkName()),
                'raw'            => $link->getLinkName()
            ];
        }
        $extends = $this->_tableDefinition->getExtends();
        $implements = $this->_tableDefinition->getImplements();
        return [
            'object'     => $this->_tableDefinition->getTableName(),
            'columns'    => $columns,
            'links'      => $links,
            'extends'    => $extends["Business"],
            'implements' => !empty($implements["Business"]) ? 'implements ' . implode(', ', $implements['Business']) : ''
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