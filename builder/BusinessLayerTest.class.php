<?php
namespace Forge\Builder;

class BusinessLayerTest extends \Forge\baseGenerator {

	protected $name = null;
	protected $fields = null;
	protected $translate = null;
	protected $extends = null;
    protected $implements = null;
	protected $location = null;
	protected $multi_lang = false;

    public function __construct($args = array()) {
		list($this->name, $this->fields, $this->translate, $this->extends, $this->implements) = $args + array(null, array(), array(), null, null);
		$this->location = \Forge\Config::path('tests') . '/unit/Objects/Business/Base/';
		if (is_array($this->translate) && !empty($this->translate)) $this->multi_lang = true;
        $this->extends = 'PHPUnit_Framework_TestCase';
	}

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setFields($fields) {
        $this->fields = $fields;
    }

    public function getFields() {
        return $this->fields;
    }
	
	public function addTranslator($opt = true) {
		$this->multi_lang = $opt;
	}

	public function setExtends($extends) {
		$this->extends = $extends;
	}

	public function getExtends() {
		return $this->extends;
	}

    public function generate() {
        if(!file_exists($this->location)) mkdir($this->location,true);
		//generate base
		$file = fopen($this->location . "base".$this->name . "Test.class.php", "w");
		$this->writeTestContent($file);
		fclose($file);
		echo "."; flush();
		chmod($this->location . "base".$this->name . "Test.class.php",0777);
		echo "."; flush();
		unset($file);
        //generate class
        if(!file_exists(substr($this->location,0,-5) . $this->name . "Test.php")) {
            $file = fopen(substr($this->location,0,-5) . $this->name . "Test.php", "w");
            $this->writeClassTestContent($file);
            fclose($file);
            echo "."; flush();
            chmod(substr($this->location,0,-5) . $this->name . "Test.php",0777);
            echo "."; flush();
            unset($file);
        }
    }

    private function writeTestContent($file) {
        fwrite($file,"<?php " . PHP_EOL);
        fwrite($file,"/** Generated Business Unit Tests for ".$this->name." */" . PHP_EOL);
        fwrite($file,"class base".$this->name."Test extends ".$this->extends."{" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        fwrite($file,"\t/** @var ".$this->name." */" . PHP_EOL);
        fwrite($file,"\tprotected \$_".\Forge\Tools::strtocamelcase($this->name)." = null;" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        fwrite($file,"\tpublic function setUp(){".PHP_EOL);
        fwrite($file,"\t\t\$this->_".\Forge\Tools::strtocamelcase($this->name)." = new ".$this->name."();".PHP_EOL);
        fwrite($file,"\t}".PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        fwrite($file,"\t/** Testing happy flow of getters & setters */" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        foreach($this->fields as $field) {
            $functionName = \Forge\Tools::strtocamelcase($field["name"], true);
            $value = $this->getValidValue($field);
            fwrite($file,"\t/**".PHP_EOL);
            fwrite($file,"\t * Testing ".$functionName.PHP_EOL);
            fwrite($file,"\t */".PHP_EOL);
            fwrite($file,"\tpublic function test".$this->name."_ValidSetAndGet".$functionName."(){".PHP_EOL);
            fwrite($file,"\t\t\$this->_".\Forge\Tools::strtocamelcase($this->name)."->set".$functionName."(".$value.");".PHP_EOL);
            fwrite($file,"\t\t\$this->assertEquals(".$value.",\$this->_".\Forge\Tools::strtocamelcase($this->name)."->get".$functionName."());".PHP_EOL);
            fwrite($file,"\t}".PHP_EOL);
            fwrite($file,"" . PHP_EOL);
        }
        fwrite($file,"\t/** Testing unhappy flow of getters & setters */" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        foreach($this->fields as $field) {
            $functionName = \Forge\Tools::strtocamelcase($field["name"], true);
            $value = $this->getInvalidValue($field);
            if($field["null"] == true && $field["length"] <= 0) continue;
            fwrite($file,"\t/**".PHP_EOL);
            fwrite($file,"\t * Testing ".$functionName.PHP_EOL);
            fwrite($file,"\t * @expectedException \\InvalidArgumentException".PHP_EOL);
            fwrite($file,"\t */".PHP_EOL);
            fwrite($file,"\tpublic function test".$this->name."_InvalidSetAndGet".$functionName."(){".PHP_EOL);
            fwrite($file,"\t\t\$this->_".\Forge\Tools::strtocamelcase($this->name)."->set".$functionName."(".$value.");".PHP_EOL);
            fwrite($file,"\t\t\$this->assertEquals(".$value.",\$this->_".\Forge\Tools::strtocamelcase($this->name)."->get".$functionName."());".PHP_EOL);
            fwrite($file,"\t}".PHP_EOL);
            fwrite($file,"" . PHP_EOL);
        }
        fwrite($file,"}" . PHP_EOL);
        fwrite($file,"?>");
    }

    public function __destroy() {
        unset($this->name,$this->fields,$this->location);
    }

    private function writeClassTestContent($file) {
        fwrite($file, "<?php" . PHP_EOL);
        fwrite($file, "/**" . PHP_EOL);
        fwrite($file, " * Forge Business Test class" . PHP_EOL);
        fwrite($file, " * --------------------" . PHP_EOL);
        fwrite($file, " * This class is the test class for ".$this->name. ".".PHP_EOL);
        fwrite($file, " * Any custom tests need to be specified here." . PHP_EOL);
        fwrite($file, " * " . PHP_EOL);
        fwrite($file, " * @author Gerry Van Bael " . PHP_EOL);
        fwrite($file, " */" . PHP_EOL);
        fwrite($file, "class " . $this->name . "Test extends base" . $this->name . "Test{" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "}" . PHP_EOL);
    }

    protected function & getValidValue($fieldDefinition){
        $value = null;
        //validate type
        switch($fieldDefinition['type']){
            case \Forge\ObjectGenerator::FIELD_TYPE_STRING:
                $value = '"A"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_INTEGER:
                $value = '1';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_FLOAT:
                $value = '1.1';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_BOOLEAN:
                $value = (mt_rand(0,1) ? 'false' : 'true');
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATETIME:
                $value = date('"Y-m-d H:i:s"');
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATE:
                $value = date('"Y-m-d"');
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_LIST:
                $value = "array('A','b','c' => 'd')";
                break;
        }
        return $value;
    }

    protected function & getInvalidValue($fieldDefinition){
        $value = null;
        //validate type
        switch($fieldDefinition['type']){
            case \Forge\ObjectGenerator::FIELD_TYPE_STRING:
                $value = $fieldDefinition["length"] > 0 ? '"'.str_repeat('A',$fieldDefinition["length"]+1).'"' : 'null';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_INTEGER:
                $value = '"ABC"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_FLOAT:
                $value = '"ABC"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_BOOLEAN:
                $value = '"ABC"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATETIME:
                $value = '"INVALID DATE"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_DATE:
                $value = '"INVALID DATE"';
                break;
            case \Forge\ObjectGenerator::FIELD_TYPE_LIST:
                $value = '"NOT A LIST"';
                break;
        }
        return $value;
    }

}