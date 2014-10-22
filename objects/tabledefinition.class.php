<?php
namespace Forge;

class TableDefinition
{
    use Translator;

    /** @var String */
    protected $_tableName;
    /** @var ColumnDefinition[] */
    protected $_columns;
    /** @var [] */
    protected $_links;

    public function __construct($tableName = null)
    {
        if (!is_null($tableName)) $this->setTableName($tableName);
    }

    public function buildFromSchema($schema = [])
    {
        foreach ($schema as $definitionType => $definition) {
            switch ($definitionType) {
                case 'Columns':
                    $this->_parseColumns($definition);
                    break;
                case 'Links':
                    $this->_parseLinks($definition);
                    break;
                case 'Translate':
                    $this->_parseTranslations($definition);
                    break;
                case 'Extends':
                    $this->_parseExtends($definition);
                    break;
                case 'Implements':
                    $this->_parseImplements($definition);
                    break;
                default:
                    throw new \Exception(sprintf($this->__('Unknown schema definition: %s'), $definitionType));
                    break;
            }
        }
    }

    /**
     * @return ColumnDefinition[]
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * @param ColumnDefinition[] $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->_columns = $columns;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->_links;
    }

    /**
     * @param mixed $links
     * @return $this
     */
    public function setLinks($links)
    {
        $this->_links = $links;
        return $this;
    }

    /**
     * @return String
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @param String $tableName
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;
        return $this;
    }

    /**
     * @param [] $definitions
     */
    protected function _parseColumns($definitions)
    {
        foreach ($definitions as $columnName => $config) {
            $column = new ColumnDefinition($columnName);

        }
    }

}