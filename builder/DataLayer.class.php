<?php
namespace Forge\Builder;

use Forge\baseGenerator;
use Forge\Config;
use Forge\TableDefinition;
use Forge\TemplateHandler;
use Forge\Tools;

/**
 * Forge DataLayer Builder
 * --------------------
 * This class generates the base and custom data class based on a defined object from the yml configuration.
 * This class contains rules and definitions of the defined attributes.
 *
 * @author  Gerry Van Bael
 * @package Forge\Builder
 */
class DataLayer extends baseGenerator
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
        $this->_objectTemplates = $this->_getTemplatesByType('objects/data');
    }

    /**
     * @throws \Exception
     */
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

    /**
     * @return array
     */
    protected function _createTokenMap()
    {
        $attributes = [];
        foreach ($this->_tableDefinition->getColumns() as $columnName => $column) {
            $attributes[] = [
                'attribute_type'       => $column->getType(),
                'attribute_name'       => Tools::camelcasetostr($column->getName()),
                'attribute_allow_null' => $column->getNull() ? 'true' : 'false',
                'attribute_length_min' => 0,
                'attribute_length_max' => $column->getLength(),
                'attribute_default'    => $this->_convertToString($column->getDefault())
            ];
        }


        $extends = $this->_tableDefinition->getExtends();
        $implements = $this->_tableDefinition->getImplements();

        return [
            'object'          => $this->_tableDefinition->getTableName(),
            'properties'      => $attributes,
            'attributes'      => $attributes,
            'attribute_rules' => $attributes,
            'extends'         => $extends["Data"],
            'implements'      => !empty($implements["Data"]) ? 'implements ' . implode(', ', $implements['Data']) : ''
        ];
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function _convertToString($value)
    {
        switch (true) {
            case $value === 0:
                return '0';
            case $value === null:
                return 'null';
            case $value === true:
                return 'true';
            case $value === false:
                return 'false';
            default:
                return '\'' . addslashes($value) . '\'';
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function __destroy()
    {
        unset($this->_tableDefinition, $this->_objectTemplates, $this->_location);
    }
}