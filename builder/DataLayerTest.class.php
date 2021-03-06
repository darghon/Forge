<?php
namespace Forge\Builder;

use Forge\baseGenerator;

class DataLayerTest extends baseGenerator
{

    protected $name = null;
    protected $fields = null;
    protected $translate = null;
    protected $extends = null;
    protected $implements = null;
    protected $location = null;
    protected $multi_lang = false;

    public function __construct($args = [])
    {
        list($this->name, $this->fields, $this->translate, $this->extends, $this->implements) = $args + [null, [], [], null, null];
        $this->location = \Forge\Config::path('tests') . '/unit/Objects/Data/Base/';
        if (is_array($this->translate) && !empty($this->translate)) $this->multi_lang = true;
        $this->extends = '\DataLayerBaseTest';

        $this->implements = null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function addTranslator($opt = true)
    {
        $this->multi_lang = $opt;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function setExtends($extends)
    {
        $this->extends = $extends;
    }

    public function generate()
    {
        if (!file_exists($this->location)) mkdir($this->location, true);
        //generate base
        $file = fopen($this->location . "base" . $this->name . "Test.class.php", "w");
        $this->writeTestContent($file);
        fclose($file);
        echo ".";
        flush();
        chmod($this->location . "base" . $this->name . "Test.class.php", 0777);
        echo ".";
        flush();
        unset($file);
        //generate class
        if (!file_exists(substr($this->location, 0, -5) . $this->name . "Test.php")) {
            $file = fopen(substr($this->location, 0, -5) . $this->name . "Test.php", "w");
            $this->writeClassTestContent($file);
            fclose($file);
            echo ".";
            flush();
            chmod(substr($this->location, 0, -5) . $this->name . "Test.php", 0777);
            echo ".";
            flush();
            unset($file);
        }
    }

    private function writeTestContent($file)
    {
        fwrite($file, "<?php " . PHP_EOL);
        fwrite($file, "namespace Data;" . PHP_EOL);
        fwrite($file, "/** Generated DataLayer Unit Tests for " . $this->name . " */" . PHP_EOL);
        fwrite($file, "class base" . $this->name . "Test extends " . $this->extends . "{" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * @return array() " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function dataProviderInvalidDataTypes(){" . PHP_EOL);
        fwrite($file, "\t\t\$" . strtolower($this->name) . " = new " . $this->name . "();" . PHP_EOL);
        fwrite($file, "\t\treturn array(" . PHP_EOL);
        $counter = 0;
        foreach ($this->fields as $field) {
            fwrite($file, "\t\t\t/* " . $field["type"] . " " . $field["name"] . " Validation */" . PHP_EOL);
            $invalidTypeList = &$this->getInvalidTypesAndLengthForField($field);
            foreach ($invalidTypeList as &$invalidType) {
                fwrite($file, "\t\t\t/* " . sprintf('%02d', $counter++) . " */ array(\$" . strtolower($this->name) . ", '" . $field["name"] . "', '" . $invalidType["type"] . "', " . $invalidType['value'] . "), //" . $invalidType['comment'] . PHP_EOL);
            }
            unset($invalidTypeList, $invalidType);
        }
        fwrite($file, "\t\t);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * @return array() " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function dataProviderValidDataTypes(){" . PHP_EOL);
        fwrite($file, "\t\t\$" . strtolower($this->name) . " = new " . $this->name . "();" . PHP_EOL);
        fwrite($file, "\t\treturn array(" . PHP_EOL);
        $counter = 0;
        foreach ($this->fields as $field) {
            fwrite($file, "\t\t\t/* " . $field["type"] . " " . $field["name"] . " Validation */" . PHP_EOL);
            $validTypeList = &$this->getValidTypesForField($field);
            foreach ($validTypeList as &$validType) {
                fwrite($file, "\t\t\t/* " . sprintf('%02d', $counter++) . " */ array(\$" . strtolower($this->name) . ", '" . $field["name"] . "', '" . $validType["type"] . "', " . $validType['value'] . "), //" . $validType['comment'] . PHP_EOL);
            }
            unset($validTypeList, $validType);
        }
        fwrite($file, "\t\t);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * @return array() " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function dataProviderDefaultValues(){" . PHP_EOL);
        fwrite($file, "\t\t\$" . strtolower($this->name) . " = new " . $this->name . "();" . PHP_EOL);
        fwrite($file, "\t\treturn array(" . PHP_EOL);
        $counter = 0;
        foreach ($this->fields as $field) {
            fwrite($file, "\t\t\t/* " . sprintf('%02d', $counter++) . " */ array(\$" . strtolower($this->name) . ", '" . $field["name"] . "', " . $this->_getDefault($field) . "), //Default value for " . $field["name"] . " is " . $this->_getDefault($field) . PHP_EOL);
        }
        fwrite($file, "\t\t);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "}" . PHP_EOL);
        fwrite($file, "?>");
    }

    protected function & getInvalidTypesAndLengthForField($fieldDefinition)
    {
        $invalidList = [];
        //validate null
        if ($fieldDefinition['null'] == false) {
            $invalidList[] = ['type' => 'Null', 'value' => "null", 'comment' => 'No NULL for ' . $fieldDefinition["name"]];
        }
        //validate type
        switch ($fieldDefinition['type']) {
            case \Forge\ObjectGenerator::FIELD_TYPE_STRING:
                if ($fieldDefinition['length'] > 0) {
                    $invalidList[] = [
                        'type'    => 'String',
                        'value'   => "str_repeat('a'," . ($fieldDefinition['length'] + 1) . ")",
                        'comment' => 'Maximum of ' . $fieldDefinition['length'] . ' characters for ' . $fieldDefinition['name']
                    ];
                }
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_INTEGER:
                $invalidList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'No floats for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "false", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                if ($fieldDefinition['length'] > 0) {
                    $invalidList[] = [
                        'type'    => 'Integer',
                        'value'   => "pow(10," . $fieldDefinition['length'] . ")",
                        'comment' => 'Maximum of ' . $fieldDefinition['length'] . ' numbers for ' . $fieldDefinition['name']
                    ];
                }
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_FLOAT:
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "false", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                if ($fieldDefinition['length'] > 0) {
                    $length = array_shift(explode(".", $fieldDefinition["length"]));
                    $invalidList[] = [
                        'type'    => 'Integer',
                        'value'   => "pow(10," . $length . ")",
                        'comment' => 'Maximum of ' . $length . ' numbers for ' . $fieldDefinition['name']
                    ];
                }
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_BOOLEAN:
                $invalidList[] = ['type' => 'Integer', 'value' => "2", 'comment' => 'No integers other than 0 or 1 for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'No floats for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATETIME:
                $invalidList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'No floats for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "false", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'2013-02-30 05:05:05'", 'comment' => 'No non-existing date for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'2013-02-15 50:05:05'", 'comment' => 'No invalid time for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'2013-01-01'", 'comment' => 'No date only for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATE:
                $invalidList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'No floats for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "false", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'2013-02-30'", 'comment' => 'No invalid date for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_LIST:
                $invalidList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'No floats for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'No non-numerical strings for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'Boolean', 'value' => "false", 'comment' => 'No boolean for ' . $fieldDefinition["name"]];
                $invalidList[] = ['type' => 'String', 'value' => "''", 'comment' => 'No blank string for ' . $fieldDefinition["name"]];
                break;
        }

        return $invalidList;
    }

    protected function & getValidTypesForField($fieldDefinition)
    {
        $validList = [];

        switch ($fieldDefinition['type']) {
            case \Forge\ObjectGenerator::FIELD_TYPE_STRING:
                $validList[] = ['type' => 'String', 'value' => "'A'", 'comment' => 'Strings allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'Float', 'value' => "1.10", 'comment' => 'Floats allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'Boolean allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'String', 'value' => "''", 'comment' => 'Blank string allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'Integer', 'value' => "1", 'comment' => 'Integer allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_INTEGER:
                $validList[] = ['type' => 'Integer', 'value' => "1", 'comment' => 'Integer allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'Float', 'value' => "1.00", 'comment' => 'Floats allowed for ' . $fieldDefinition["name"] . ' if castable to integer'];
                $validList[] = ['type' => 'String', 'value' => "'1'", 'comment' => 'Numerical strings allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_FLOAT:
                $validList[] = ['type' => 'Float', 'value' => "1.00", 'comment' => 'Floats allowed for ' . $fieldDefinition["name"] . ''];
                $validList[] = ['type' => 'Integer', 'value' => "1", 'comment' => 'Integer allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'String', 'value' => "'1.10'", 'comment' => 'Numerical strings allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_BOOLEAN:
                $validList[] = ['type' => 'Boolean', 'value' => "true", 'comment' => 'Boolean allowed for ' . $fieldDefinition["name"]];
                $validList[] = ['type' => 'Integer', 'value' => "1", 'comment' => 'Integer (0 or 1) allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATETIME:
                $validList[] = ['type' => 'String', 'value' => "'2013-01-01 05:05:05'", 'comment' => 'Datetime allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATE:
                $validList[] = ['type' => 'String', 'value' => "'2013-01-01'", 'comment' => 'Date only allowed for ' . $fieldDefinition["name"]];
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_LIST:
                $invalidList[] = ['type' => 'String', 'value' => "serialize(array())", 'comment' => 'Serialized arrays allowed for ' . $fieldDefinition["name"]];
                break;
        }

        return $validList;
    }

    private function writeClassTestContent($file)
    {
        fwrite($file, "<?php" . PHP_EOL);
        fwrite($file, "namespace Data;" . PHP_EOL);
        fwrite($file, "/**" . PHP_EOL);
        fwrite($file, " * Forge DataLayer Test class" . PHP_EOL);
        fwrite($file, " * --------------------" . PHP_EOL);
        fwrite($file, " * This class is the test class for " . $this->name . "." . PHP_EOL);
        fwrite($file, " * Any custom tests need to be specified here." . PHP_EOL);
        fwrite($file, " * " . PHP_EOL);
        fwrite($file, " * @author Gerry Van Bael " . PHP_EOL);
        fwrite($file, " */" . PHP_EOL);
        fwrite($file, "class " . $this->name . "Test extends base" . $this->name . "Test{" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "}" . PHP_EOL);
    }

    public function __destroy()
    {
        unset($this->name, $this->fields, $this->location);
    }

}