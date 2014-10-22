<?php
namespace Forge;

class DatabaseSchema
{
    use Translator;

    /** @var String */
    protected $_schema_folder;
    /** @var TableDefinition[] */
    protected $_table_definitions;

    public function __construct($schema_folder = null)
    {
        if (!is_null($schema_folder)) $this->setSchemaFolder($schema_folder);
    }

    public function loadSchema()
    {
        if (is_null($this->_schema_folder)) throw new \Exception($this->__('No schema folder has been defined'));

        $schema = [];
        if (false !== ($handle = opendir($this->_schema_folder))) {
            while (false !== ($file = readdir($handle))) {
                if (substr($file, 0, 1) !== "." && substr($file, strlen($file) - 1) !== "~") {
                    $schema = array_merge($schema, Config::get(substr($file, 0, strrpos($file, ".")), $this->_schema_folder));
                }
            }
            closedir($handle);
        }
        if ($this->_parseSchema($schema)) {
            //basic check is ok, lets parse each table

        }
        return $this;
    }

    protected function _parseSchema($schema)
    {
        //check if all Object types are valid
        foreach ($schema as $table_name => $table_definition) {
            //check the columns definitions, if type is a collection of other objects, link tables may be added as well
            if (isset($table_definition['Columns'])) $this->_parseColumns($schema, $table_definition['Columns'], $table_name);
            //Check if translation table needs to be added as well
            if (isset($table_definition['Translate'])) $this->_parseTranslations($schema, $table_definition['Translate'], $table_name);
        }

        return true; //only reached when no errors are found.
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
     * @param $schema
     * @param $table_definition
     * @param $table_name
     * @throws \Exception
     */
    protected function _parseColumns(&$schema, $table_definition, $table_name)
    {
        foreach ($table_definition as $field_name => $field_definition) {
            $type = !is_array($field_definition) ? $field_definition : (!isset($field_definition['Type']) ? $field_definition['type'] : $field_definition['Type']);
            switch (strtolower($type)) {
                case ColumnDefinition::TYPE_STRING:
                case ColumnDefinition::TYPE_INTEGER:
                case ColumnDefinition::TYPE_FLOAT:
                case ColumnDefinition::TYPE_BOOLEAN:
                case ColumnDefinition::TYPE_DATE:
                case ColumnDefinition::TYPE_DATETIME:
                case ColumnDefinition::TYPE_LIST:
                    continue; //all ok
                    break;
                default: //check if passed type is a passed table //Might also be a collection of a table
                    if (substr($type, -2) == '[]') {
                        if (!isset($schema[substr($type, 0, -2)])) throw new \Exception(sprintf($this->__('Unable to add field %s to table %s with type %s: Type does not exist'), $field_name, $table_name, $type));
                        else {
                            //Add link table to schema
                            $schema[$table_name . substr($type, 0, -2)] = [
                                'Columns' => [
                                    Tools::camelcasetostr($table_name, '_') => $table_name,
                                    Tools::camelcasetostr(substr($type, 0, -2)) => substr($type, 0, -2)
                                ]
                            ];
                        }
                    } else {
                        if (!isset($schema[$type])) throw new \Exception(sprintf($this->__('Unable to add field %s to table %s with type %s: Type does not exist'), $field_name, $table_name, $type));
                    }
                    continue;
                    break;
            }
        }
    }

    protected function _parseTranslations(&$schema, $table_definition, $table_name)
    {

    }


}