<?php
namespace Forge;

class TableDefinition
{
    use Translator;

    /** @var string */
    protected $_tableName;
    /** @var ColumnDefinition[] */
    protected $_columns;
    /** @var array */
    protected $_links;
    /** @var array */
    protected $_validFieldTypes = [
        ColumnDefinition::TYPE_STRING,
        ColumnDefinition::TYPE_INTEGER,
        ColumnDefinition::TYPE_FLOAT,
        ColumnDefinition::TYPE_BOOLEAN,
        ColumnDefinition::TYPE_DATE,
        ColumnDefinition::TYPE_DATETIME,
        ColumnDefinition::TYPE_LIST
    ];
    /** @var array */
    protected $_extends = [
        'Business' => '\Forge\BusinessLayer',
        'Finder' => '\Forge\Finder',
        'Data' => '\Forge\DataLayer'
    ];
    /** @var array */
    protected $_implements = [
        'Business' => [],
        'Finder' => [],
        'Data' => []
    ];

    /**
     * @param null|string $tableName
     * @param array       $definitions
     *
     * @throws \Exception
     */
    public function __construct($tableName = null, $definitions = [])
    {
        if (!is_null($tableName)) $this->setTableName($tableName);
        if (!empty($definitions)) $this->buildFromSchema($definitions);
    }

    /**
     * @param array $definitions
     *
     * @throws \Exception
     */
    public function buildFromSchema($definitions = [])
    {
        foreach ($definitions as $definitionType => $definition) {
            switch ($definitionType) {
                case 'Columns':
                    $this->_parseColumns($definition);
                    break;
                case 'Links':
                    $this->_parseLinks($definition);
                    break;
                case 'Translate':
                    $this->_parseTranslations(is_array($definition) ? $definition : [$definition]);
                    break;
                case 'Extends':
                    $this->_parseExtends(is_array($definition) ? $definition : [$definition]);
                    break;
                case 'Implements':
                    $this->_parseImplements(is_array($definition) ? $definition : [$definition]);
                    break;
                default:
                    throw new \Exception(sprintf($this->__('Unknown schema definition: %s'), $definitionType));
                    break;
            }
        }
    }

    /**
     * @param array $definitions
     */
    protected function _parseColumns($definitions = [])
    {
        foreach ($definitions as $field_name => $field_definition) {
            $column = new ColumnDefinition();
            $column->setName(Tools::camelcasetostr($field_name));
            if (is_array($field_definition)) {
                if (isset($field_definition['Null'])) $column->setNull(!!$field_definition['Null']);
                if (isset($field_definition['Default'])) $column->setDefault($field_definition['Default']);
                if (isset($field_definition['Length'])) $column->setLength((int)$field_definition['Length']);
            }
            $type = !is_array($field_definition) ? $field_definition : (!isset($field_definition['Type']) ? $field_definition['type'] : $field_definition['Type']);
            if (!in_array(strtolower($type), $this->_validFieldTypes)) {
                $column->setType(ColumnDefinition::TYPE_INTEGER);
                $column->setName($column->getName().'_id');
            } else {
                $column->setType(strtolower($type));
            }
            $this->_columns[$field_name] = $column;
        }
    }

    /**
     * @param array $definitions
     */
    protected function _parseLinks($definitions = [])
    {
        foreach($definitions as $link_name => $link_definition) {
            $link = new LinkDefinition($link_name, $link_definition);
            $link->setFromObject($this->getTableName());
            $this->_links[] = $link;
            unset($link);
        }
    }

    /**
     * @param array $definitions
     */
    protected function _parseTranslations($definitions = [])
    {
        foreach($definitions as $definition) {
            if(in_array($definition, array_keys($this->_columns))) {
                $this->_columns[$definition]->setTranslated(true);
            }
            else {
                throw new \InvalidArgumentException(sprintf($this->__('Trying to translate a column in %s that does not exits(%s)'), $this->getTableName(), $definition));
            }
        }
    }

    /**
     * @param array $definitions
     */
    protected function _parseExtends($definitions)
    {
        foreach($definitions as $type => $definition){
            if ($definition !== null && $definition !== '~') {
                if (!class_exists($definition)) {
                    throw new \InvalidArgumentException(sprintf($this->__('Trying to extend a class in %s that does not exist(%s).'),$this->getTableName(), $definition));
                }
                else {
                    switch(strtolower($type)) {
                        case 'businesslayer':
                            if (!$definition::is_a('Forge\\BusinessLayer')) {
                                throw new \InvalidArgumentException(sprintf($this->__('Trying to extend a business class in %s that does not extend Forge\BusinessLayer (%s).'), $this->getTableName(), $definition));
                            }
                            else{
                                $this->_extends['Business'] = $definition;
                            }
                            break;
                        case 'finder':
                            if (!$definition::is_a('Forge\\Finder')) {
                                throw new \InvalidArgumentException(sprintf($this->__('Trying to extend a finder class in %s that does not extend Forge\Finder (%s).'), $this->getTableName(), $definition));
                            }
                            else{
                                $this->_extends['Finder'] = $definition;
                            }
                            break;
                        case 'datalayer':
                            if (!$definition::is_a('Forge\\DataLayer')) {
                                throw new \InvalidArgumentException(sprintf($this->__('Trying to extend a data class in %s that does not extend Forge\DataLayer (%s).'), $this->getTableName(), $definition));
                            }
                            else{
                                $this->_extends['Data'] = $definition;
                            }
                            break;
                        default:
                            if(is_numeric($type)) {
                                if (!$definition::is_a('Forge\\BusinessLayer')) {
                                    throw new \InvalidArgumentException(sprintf($this->__('Trying to extend a business class in %s that does not extend BusinessLayer (%s).'), $this->getTableName(), $definition));
                                }
                                else{
                                    $this->_extends['Business'] = $definition;
                                }
                            }
                            else{
                                throw new \InvalidArgumentException(sprintf($this->__('Trying to extend an unknown type in %s (%s => %s).'), $this->getTableName(), $type, $definition));
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param array $definitions
     */
    protected function _parseImplements($definitions = [])
    {
        foreach($definitions as $type => $definition) {
            if ($definition !== null && $definition !== '~' && !empty($definition)) {
                foreach ($definition as $imp) {
                    if (!interface_exists($imp)) {
                        throw new \InvalidArgumentException(sprintf($this->__('Trying to implement a interface in %s that does not exist(%s).'), $this->getTableName(), $imp));
                    }
                }
                if(is_numeric($type)) $type = 'Business';

                if(array_key_exists($type, $this->_implements)) {
                    $this->_implements[$type] = array_merge($this->_implements[$type],$definition);
                }
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
     *
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->_columns = $columns;

        return $this;
    }

    /**
     * @return LinkDefinition[]
     */
    public function getLinks()
    {
        return $this->_links;
    }

    /**
     * @param LinkDefinition[] $links
     *
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
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtends()
    {
        return $this->_extends;
    }

    /**
     * @param array $extends
     *
     * @return $this
     */
    public function setExtends(array $extends)
    {
        $this->_extends = $extends;
        return $this;
    }

    /**
     * @return array
     */
    public function getImplements()
    {
        return $this->_implements;
    }

    /**
     * @param array $implements
     *
     * @return $this
     */
    public function setImplements(array $implements)
    {
        $this->_implements = $implements;
        return $this;
    }

}