<?php
namespace Forge;

abstract class ObjectGenerator extends baseGenerator
{

    const FIELD_TYPE_STRING = 'string';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_FLOAT = 'double';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_DATE = 'date';
    const FIELD_TYPE_DATETIME = 'datetime';
    const FIELD_TYPE_LIST = 'list';

    protected function & getDatabaseSchema()
    {
        $schema = [];
        if (false !== ($handle = opendir(Config::path("root") . "/config/database/"))) {
            while (false !== ($file = readdir($handle))) {
                //Do not load files that start with . and/or end with ~
                if (substr($file, 0, 1) !== "." && substr($file, strlen($file) - 1) !== "~") {
                    $schema = array_merge($schema, Config::get("database/" . substr($file, 0, strrpos($file, "."))));
                }
            }
            closedir($handle);
        }
        $this->validateSchema($schema);

        return $schema;
    }

    protected function validateSchema($schema)
    {
        //check if all Object types are valid
        foreach ($schema as $table_name => $table_definition) {
            foreach ($table_definition['Columns'] as $field_name => $field_definition) {
                $type = !is_array($field_definition) ? $field_definition : (!isset($field_definition['Type']) ? $field_definition['type'] : $field_definition['Type']);
                switch (strtolower($type)) {
                    case self::FIELD_TYPE_STRING:
                    case self::FIELD_TYPE_INTEGER:
                    case self::FIELD_TYPE_FLOAT:
                    case self::FIELD_TYPE_BOOLEAN:
                    case self::FIELD_TYPE_DATE:
                    case self::FIELD_TYPE_DATETIME:
                    case self::FIELD_TYPE_LIST:
                        continue; //all ok
                        break;
                    default: //check if passed type is a passed table //Might also be a collection of a table
                        if (substr($type, -2) == '[]') $type = substr($type, 0, -2);
                        if (!isset($schema[$type])) throw new \Exception('Unable to add field ' . $field_name . ' to table ' . $table_name . ' with type ' . $field_definition . ': Type does not exist');
                        continue;
                        break;
                }
            }
        }
    }

    protected function processTable($name, &$table)
    {
        if (!isset($table["Links"])) $table["Links"] = [];
        $fields = $this->processFields($table["Columns"], $table["Links"]);
        $translate_buffer = [];
        if (isset($table["Translate"])) {
            //set default ID and language fields
            $translate_buffer[] = ["name" => "id", "type" => "integer", "length" => "20", "default" => "null", "null" => "false"];
            $translate_buffer[] = ["name" => $name . "_id", "type" => "integer", "length" => "20", "default" => "null", "null" => "false"];
            $translate_buffer[] = ["name" => "lang", "type" => "string", "length" => "2", "default" => "EN", "null" => "false"];
            //copy all translate values from fields
            foreach ($table["Translate"] as $trans) {
                foreach ($fields as $key => $field) {
                    if ($trans == $field["name"]) {
                        $translate_buffer[] = $field;
                        unset($fields[$key]);
                    }
                }
            }
            //System fields for record version and deleted at timestamp
            $translate_buffer[] = ["name" => "_record_version", "type" => "integer", "length" => "20", "default" => "0", "null" => "false"];
            $translate_buffer[] = ["name" => "_deleted_at", "type" => "datetime", "length" => "10", "default" => "null", "null" => "true"];
        }
        $links = [];
        if (!empty($table["Links"]) || count($translate_buffer) > 0) {
            if (!empty($table["Links"])) {
                $links = $this->processLinks($table["Links"]);
            }
        }
        //Pass if any Extending classes have been defined
        $extends = array_merge(['Business' => '~', 'Finder' => '~', 'Data' => '~'], isset($table['Extends']) ? $table['Extends'] : []);
        $implements = array_merge(['Business' => '~', 'Finder' => '~', 'Data' => '~'], isset($table['Implements']) ? $table['Implements'] : []);

        return [$fields, $links, $translate_buffer, $extends, $implements];
    }

    /**
     * Private static function to process the fields of a given table schema.
     *
     * @param array $table
     *
     * @return array $fields
     */
    protected function processFields(&$table, &$links = [])
    {
        //create Fields
        $fields = [];
        //standard ID field
        $fields[] = ["name" => "id", "type" => "integer", "length" => "20", "default" => "null", "null" => "false"];
        foreach ($table as $column_name => &$column) {
            $field = $this->processColumnDefinition($column_name, $column, $links);
            $fields[] = $field;
        }
        //Standard Version & Delflag field
        $fields[] = ["name" => "_record_version", "type" => "integer", "length" => "20", "default" => "0", "null" => "false"];
        $fields[] = ["name" => "_deleted_at", "type" => "datetime", "length" => "20", "default" => "null", "null" => "true"];

        return $fields;
    }

    protected function processColumnDefinition($column_name, $definition, &$links)
    {
        $field = [];
        if (is_array($definition)) {
            $field["type"] = $this->defineFieldType((isset($definition["Type"]) ? strtolower($definition["Type"]) : "string"), $links, $column_name);
            $field["length"] = (isset($definition["Length"])) ? $definition["Length"] : "0";
            $field["default"] = (isset($definition["Default"])) ? (($definition["Default"] === true) ? 'true' : (($definition["Default"] === false) ? 'false' : $definition["Default"])) : "null";
            $field["null"] = (isset($definition["Null"])) ? $definition["Null"] : "true";
        } else {
            $field["type"] = $this->defineFieldType($definition, $links, $column_name);
            switch (strtolower($definition)) {
                case self::FIELD_TYPE_INTEGER:
                    $field["length"] = "20";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                case self::FIELD_TYPE_FLOAT:
                    $field["length"] = "16,4";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                case self::FIELD_TYPE_BOOLEAN:
                    $field["length"] = "1";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                case self::FIELD_TYPE_DATE:
                    $field["length"] = "10";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                case self::FIELD_TYPE_DATETIME:
                    $field["length"] = "20";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                case self::FIELD_TYPE_STRING:
                case self::FIELD_TYPE_LIST:
                    $field["length"] = "0";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
                default: //check if passed type is a passed table
                    $field["length"] = "20";
                    $field["default"] = "null";
                    $field["null"] = "true";
                    break;
            }
        }

        $field["name"] = Tools::camelcasetostr($column_name);

        return $field;
    }

    protected function defineFieldType($type, &$links = [], &$field_name = '')
    {
        switch (strtolower($type)) {
            case self::FIELD_TYPE_STRING:
            case self::FIELD_TYPE_INTEGER:
            case self::FIELD_TYPE_BOOLEAN:
            case self::FIELD_TYPE_FLOAT:
            case self::FIELD_TYPE_DATE:
            case self::FIELD_TYPE_DATETIME:
            case self::FIELD_TYPE_LIST:
                return strtolower($type);
                break;
            default: //passed type needs to be reverted to the ForeignKey
                $links[$field_name] = [
                    'Local'  => $field_name . '_id',
                    'Target' => $type
                ];
                $field_name .= 'ID';

                return self::FIELD_TYPE_INTEGER;
                break;
        }
    }

    /**
     * Private static function to process the links of a given table schema
     *
     * @param array $links
     *
     * @return array $relations
     */
    protected function processLinks($links)
    {
        //create Links
        $relations = [];
        foreach ($links as $relation_name => &$relation) {
            $link = [];
            $link["name"] = $relation_name;
            $link["target"] = $relation["Target"];
            $link["local"] = $relation["Local"];
            $relations[] = $link;
        }

        return $relations;
    }
}