<?php
namespace Forge;

class DatabaseSchema
{
    use Translator;

    /** @var String */
    protected $_schema_folder;
    /** @var TableDefinition[] */
    protected $_table_definitions;
    /** @var array */
    protected $_working_schema = [];

    /**
     * @param null|string $schema_folder
     */
    public function __construct($schema_folder = null)
    {
        if (!is_null($schema_folder)) $this->setSchemaFolder($schema_folder);
    }

    /**
     * Public function that loads all yaml files from the schema folder
     * All contents are delegated to the parseSchema function that calculates the actual schema
     *
     * @return $this
     * @throws \Exception
     */
    public function loadSchema()
    {
        if (is_null($this->_schema_folder)) throw new \Exception($this->__('No schema folder has been defined'));

        $this->_working_schema = [];
        if (false !== ($handle = opendir($this->_schema_folder))) {
            while (false !== ($file = readdir($handle))) {
                if (substr($file, 0, 1) !== "." && substr($file, strlen($file) - 1) !== "~") {
                    $this->_working_schema = array_merge($this->_working_schema, Config::get(substr($file, 0, strrpos($file, ".")), $this->_schema_folder));
                }
            }
            closedir($handle);
        }

        if ($this->_parseSchema()) {
            //basic check is ok, lets parse each table
            foreach($this->_working_schema as $table_name => $definitions) {
                $tableDefinition = new TableDefinition($table_name, $definitions);
                $this->_table_definitions[] = $tableDefinition;
            }
        }

        return $this;
    }

    /**
     * @return bool $success
     * @throws \Exception
     */
    protected function _parseSchema()
    {
        $translationAdded = false;
        //check if all Object types are valid
        while (list($table_name, $table_definition) = each($this->_working_schema)) {
            if (!isset($table_definition['Behaviors'])) $table_definition['Behaviors'] = [];
                //check the columns definitions, if type is a collection of other objects, link tables may be added as well
            $this->_validateBehaviors($table_definition['Behaviors'], $table_name, $table_definition);
            if (isset($table_definition['Columns'])) $this->_validateColumns($table_definition['Columns'], $table_name, $table_definition);
            if (isset($table_definition['Translation']) && !$translationAdded){
                $translationAdded = true;
                $this->_addTranslationTable();
            }
        }
        return true; //only reached when no errors are found.
    }

    /**
     * @param array  $behaviors
     * @param string $table_name
     * @param array  $table_definition
     *
     * @throws \Exception
     */
    protected function _validateBehaviors($behaviors, $table_name, $table_definition)
    {
        /** @var Apply global config to each table **/
        $columns = $table_definition['Columns'];
        $table_definition['Columns']['id'] = ['Type' => 'Integer', 'Length' => '10', 'Null' => false];
        foreach($columns as $key => $column) $table_definition['Columns'][$key] = $column;
        $table_definition['Columns']['_recordVersion'] = ['Type' => 'Integer', 'Length' => '10', 'Null' => false, 'Default' => 0];
        $table_definition['Columns']['_deletedAt'] = ['Type' => 'Datetime', 'Length' => '20'];

        $this->_working_schema[$table_name] = $table_definition;

        foreach ($behaviors as $behavior) {

            throw new \Exception('Behavior: ' . $behavior . ' not recognised' . PHP_EOL);
        }
    }

    /**
     * @param array  $table_definition
     * @param string $table_name
     *
     * @throws \Exception
     */
    protected function _validateColumns($table_definition, $table_name)
    {
        foreach ($table_definition as $field_name => $field_definition) {
            $type = !is_array($field_definition) ? $field_definition : (!isset($field_definition['Type']) ? $field_definition['type'] : $field_definition['Type']);
            if (!in_array(strtolower($type), ColumnDefinition::$validFieldTypes)) {
                if (substr($type, -2) == '[]') {
                    if (!isset($this->_working_schema[substr($type, 0, -2)])) {
                        throw new \Exception(sprintf($this->__('Unable to add field %s to table %s with type %s: Type does not exist'), $field_name, $table_name, $type));
                    }
                    else {
                        $this->_addComboToSchema($table_name, $field_name, $type);
                    }
                } else {
                    if (!isset($this->_working_schema[$type])) {
                        throw new \Exception(sprintf($this->__('Unable to add field %s to table %s with type %s: Type does not exist'), $field_name, $table_name, $type));
                    }
                    else{
                        //rename current column to foreign key
                        $this->_working_schema[$table_name]['Columns'][Tools::camelcasetostr($field_name).'_id'] = 'integer';
                        unset($this->_working_schema[$table_name]['Columns'][$field_name]);
                        $this->_addLink($table_name, Tools::strtocamelcase($field_name, true), Tools::camelcasetostr($field_name).'_id', $type);
                        $this->_addLink($type, $table_name.'s', 'id', $table_name.'[]');
                    }
                }
            }
        }
    }

    /**
     * @param string $table_name
     * @param string $field_name
     * @param string $type
     */
    protected function _addComboToSchema($table_name, $field_name, $type)
    {
        $new_table_name = $table_name . substr($type, 0, -2);
        if (isset($this->_working_schema[substr($type, 0, -2) . $table_name])) $new_table_name = substr($type, 0, -2) . $table_name;

        $columns = [
            Tools::camelcasetostr($table_name)          => $table_name,
            Tools::camelcasetostr(substr($type, 0, -2)) => substr($type, 0, -2)
        ];

        $this->_addNewTable($new_table_name, $columns);

        //remove field from current table
        unset($this->_working_schema[$table_name]['Columns'][$field_name]);

        //add links
        $this->_addLink($table_name, Tools::strtocamelcase($field_name, true), 'id', $type, $new_table_name);
        $this->_addLink(substr($type, 0, -2), $table_name . 's', 'id', $table_name . '[]', $new_table_name);
    }

    /**
     * @param string $new_table_name
     * @param array  $columns
     *
     * @return bool
     */
    protected function _addNewTable($new_table_name, $columns)
    {
        if (!isset($this->_working_schema[$new_table_name])) $this->_working_schema[$new_table_name] = [];
        if (!isset($this->_working_schema[$new_table_name]['Columns'])) $this->_working_schema[$new_table_name]['Columns'] = [];

        $this->_working_schema[$new_table_name]['Columns'] = array_merge($this->_working_schema[$new_table_name]['Columns'], $columns);
        return true;
    }

    /**
     * @param string      $table_name
     * @param string      $link_name
     * @param string      $local_identifier
     * @param string      $target_table
     * @param null|string $link_table
     *
     * @return bool
     */
    protected function _addLink($table_name, $link_name, $local_identifier, $target_table, $link_table = null)
    {
        $link = [
            'Local'  => $local_identifier,
            'Target' => $target_table
        ];

        if ($link_table != null) {
            $link['Link'] = $link_table;
        }

        if (!isset($this->_working_schema[$table_name]['Links'])) $this->_working_schema[$table_name]['Links'] = [];
        $this->_working_schema[$table_name]['Links'] = array_merge($this->_working_schema[$table_name]['Links'], [
            $link_name => $link
        ]);
        return true;
    }

    /**
     * @return bool
     */
    protected function _addTranslationTable()
    {
        return $this->_addNewTable('_I18n', [
            'Tag'  => ['Type' => 'String', 'Length' => '25', 'Null' => false],
            'Lang' => ['Type' => 'String', 'Length' => '2', 'Null' => false],
            'Text' => 'String'
        ]);
    }

    /**
     * @return String
     */
    public function getSchemaFolder()
    {
        return $this->_schema_folder;
    }

    /**
     * @param String $schema_folder
     */
    public function setSchemaFolder($schema_folder)
    {
        $this->_schema_folder = $schema_folder;
    }

    /**
     * @return TableDefinition[]
     */
    public function getTableDefinitions()
    {
        return $this->_table_definitions;
    }

    /**
     * @param TableDefinition[] $table_definitions
     *
     * @return $this
     */
    public function setTableDefinitions(array $table_definitions)
    {
        $this->_table_definitions = $table_definitions;
        return $this;
    }


}