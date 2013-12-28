<?php
namespace Core\Builder;

class DataLayer extends \Core\baseGenerator {

	protected $name = null;
	protected $fields = null;
	protected $translate = null;
	protected $extends = null;
    protected $implements = null;
	protected $location = null;
	protected $multi_lang = false;

    public function __construct($args = array()) {
		list($this->name, $this->fields, $this->translate, $this->extends, $this->implements) = $args + array(null, array(), array(), null, null);
		$this->location = \Core\Config::path('objects') . '/data/';
		if (is_array($this->translate) && !empty($this->translate))
			$this->multi_lang = true;
		if($this->extends == null || $this->extends == '~') $this->extends = '\Core\DataLayer';
		else{
			if(!class_exists($this->extends)) trigger_error('Trying to extend a class in '.$this->name.' that does not exist('.$this->extends.').');
			else{
				$test = $this->extends;
				if(!$test::is_a('Core\\DataLayer')) trigger_error('Trying to extend a class in '.$this->name.' that does not extend DataLayer('.$this->extends.').');
				unset($test);
			}
		}
        if($this->implements == null || $this->implements == '~') $this->implements = '';
        else{
            if(is_array($this->implements)){
                foreach($this->implements as $imp){
                    if(!interface_exists($imp)) throw new \InvalidArgumentException('Trying to implement a interface in '.$this->name.' that does not exist('.$imp.').');
                }
            }
            else if(!interface_exists($this->implements)) throw new \InvalidArgumentException('Trying to implement a interface in '.$this->name.' that does not exist('.$this->implements.').');

            $this->implements = ' implements '.(is_array($this->implements) ? implode(', ',$this->implements) : $this->implements);
        }
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

		//generate base
		$file = fopen($this->location . "base/base" . $this->name . ".class.php", "w");
		$this->writeBaseContent($file);
		fclose($file);
		echo "."; flush();
		chmod($this->location . "base/base" . $this->name . ".class.php",0777);
		echo "."; flush();
		unset($file);

		//generate class is not exist (need to preserve user code)
		if (!file_exists($this->location .$this->name . ".class.php")) {
			$file = fopen($this->location .$this->name . ".class.php", "w");
			$this->writeClassContent($file);
			fclose($file);
			echo "."; flush();
			chmod($this->location . $this->name . ".class.php",0777);
			echo "."; flush();
			unset($file);
		}

		if (is_array($this->translate) && !empty($this->translate)) {
			//register the translation handlers
            \Core\Generator::getInstance()->build('datalayer',array($this->name.'_i18n',$this->translate, array()));
		}

    }

    private function writeBaseContent($file) {
        fwrite($file,"<?php " . PHP_EOL);
        fwrite($file, "namespace Data;".PHP_EOL);
        fwrite($file,"abstract class base".$this->name." extends ".$this->extends.$this->implements."{" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        foreach($this->fields as $field) {
			fwrite($file,"\tprotected \$".$field["name"]." = null;" . PHP_EOL);
        }
        fwrite($file,"" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Object rules, returns a list of validation rules for this data object." . PHP_EOL);
        fwrite($file, "\t * @return array(\$rules) " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file,"\tprotected function _rules(){" . PHP_EOL);
        fwrite($file,"\t\treturn array(" . PHP_EOL);
        foreach($this->fields as $field) {
            fwrite($file,"\t\t\t'".$field["name"]."' => array(" . PHP_EOL);
            //Validate null value
            fwrite($file,"\t\t\t\t'allowNull' => ".($field["null"] == true ? 'true' : 'false') . "," . PHP_EOL);
            //Validate length
            if($field["length"] > 0) {
                fwrite($file,"\t\t\t\t'length' => array('min' => 0, 'max' => ");
                if($field["type"] == \Core\ObjectGenerator::FIELD_TYPE_FLOAT) {
                    fwrite($file,(array_sum(explode(".",$field["length"]))+1));
                }
                else {
                    fwrite($file,$field["length"]);
                }
                fwrite($file, "),".PHP_EOL);
            }
            //validate type
            fwrite($file,"\t\t\t\t'type' => '".$field["type"] . "'," . PHP_EOL);
            //validate default
            fwrite($file,"\t\t\t\t'default' => ".$this->getDefault($field) . "," . PHP_EOL);
            fwrite($file, "\t\t\t),".PHP_EOL);
        }
        fwrite($file,"\t\t);" . PHP_EOL);
        fwrite($file,"\t}" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        fwrite($file,"}" . PHP_EOL);
        fwrite($file,"?>");
    }

    private function writeClassContent($file) {
        fwrite($file,"<?php" . PHP_EOL);
        fwrite($file, "namespace Data;".PHP_EOL);
        fwrite($file,"\tclass ".$this->name." extends base".$this->name."{" . PHP_EOL);
        fwrite($file,"" . PHP_EOL);
        fwrite($file,"\t}" . PHP_EOL);
        fwrite($file,"?>" . PHP_EOL);
    }

    public function __destroy() {
        unset($this->name,$this->fields,$this->location);
    }

}