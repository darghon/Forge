<?php
namespace Core\Builder;

class Finder extends \Core\baseGenerator {

	protected $name = null;
	protected $fields = null;
	protected $translate = null;
	protected $extends = null;
    protected $implements = null;
	protected $location = null;
	protected $multi_lang = false;

	public function __construct($args = array()) {
		list($this->name, $this->fields, $this->translate, $this->extends, $this->implements) = $args + array(null, array(), array(), null, null);
		$this->location = \Core\Config::path('objects') . '/finders/';
		if (is_array($this->translate) && !empty($this->translate))
			$this->multi_lang = true;
		if($this->extends == null || $this->extends == '~') $this->extends = '\Core\Finder';
		else{
			if(!class_exists($this->extends)) trigger_error('Trying to extend a class in '.$this->name.' that does not exist('.$this->extends.').');
			else{
				$test = $this->extends;
				if(!$test::is_a('Core\\Finder')) trigger_error('Trying to extend a class in '.$this->name.' that does not extend Finder('.$this->extends.').');
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
            \Core\Generator::getInstance()->build('finder',array($this->name.'_i18n',$this->translate, array()));
		}
	}

	private function writeBaseContent($file) {
        fwrite($file, "<?php ".PHP_EOL);
        fwrite($file, "namespace Finder;".PHP_EOL);
		fwrite($file, "abstract class base" . $this->name . " extends ".$this->extends."{".PHP_EOL);
		fwrite($file, PHP_EOL);
		foreach ($this->fields as $field) {
			if (substr($field['name'], -2) == 'ID' && $field['name'] != 'ID') {
				fwrite($file, "\t/**" . PHP_EOL);
				fwrite($file, "\t * Lazy loading function that retrieves the selected " . $field["name"] . "" . PHP_EOL);
				fwrite($file, "\t * @return " . substr($field["name"],0,-2) . " " . PHP_EOL);
				fwrite($file, "\t */" . PHP_EOL);
				fwrite($file, "\tpublic function & by" . $field['name'] . "(\$" . $field['name'] . ", \$page = false,\$pagesize = 20){".PHP_EOL);
				fwrite($file, "\t\t\$return = array();".PHP_EOL);
				fwrite($file, "\t\t\$this->db->setQuery(\"SELECT * FROM \".\$this->db->getPrefix().\"" . $this->name . " WHERE _deletedAt IS NULL AND " . $field['name'] . " = '\".$" . $field['name'] . ".\"' \".(\$page !== false ? \" Limit \".((\$page-1)*\$pagesize).\",\".\$pagesize : \"\").\";\");".PHP_EOL);
				fwrite($file, "\t\t\$this->db->execute();".PHP_EOL);
				fwrite($file, "\t\tif(\$this->db->hasRecords()){".PHP_EOL);
				fwrite($file, "\t\t\twhile(\$row = \$this->db->getRecord()){".PHP_EOL);
				fwrite($file, "\t\t\t\t\$return[] = &\$this->createObject(\$row);".PHP_EOL);
				fwrite($file, "\t\t\t}".PHP_EOL);
				fwrite($file, "\t\t}".PHP_EOL);
				fwrite($file, "\t\treturn \$return;".PHP_EOL);
				fwrite($file, "\t}".PHP_EOL);
				fwrite($file, PHP_EOL);
			}
            if (strpos($this->name,"_i18n") >= -1){
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Language loader for " . str_replace('_i18n','',$field["name"]) . PHP_EOL);
                fwrite($file, "\t * @return " . $field["name"] . " " . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function & getTranslationByID(\$id, \$lang){".PHP_EOL);
                fwrite($file, "\t\t\$def = false;".PHP_EOL);
                fwrite($file, "\t\t\$this->db->setQuery(\"SELECT * FROM \".\$this->db->getPrefix().\"" . $this->name . " WHERE " . str_replace("_i18n", "", $this->name) . "ID = '\".\$id.\"' And Lang = '\".\$lang.\"';\");".PHP_EOL);
                fwrite($file, "\t\t\$this->db->execute();".PHP_EOL);
                fwrite($file, "\t\tif(\$this->db->hasRecords()){".PHP_EOL);
                fwrite($file, "\t\t\t\$row = \$this->db->getRecord();".PHP_EOL);
                fwrite($file, "\t\t\treturn \$this->createObject(\$row);".PHP_EOL);
                fwrite($file, "\t\t}".PHP_EOL);
                fwrite($file, "\t\telse{".PHP_EOL);
                fwrite($file, "\t\t\treturn \$def;".PHP_EOL);
                fwrite($file, "\t\t}".PHP_EOL);
                fwrite($file, "\t}".PHP_EOL);
                fwrite($file, PHP_EOL);
            }
        }
		fwrite($file, "\tpublic function validate(){".PHP_EOL);
		fwrite($file, "\t\treturn true;".PHP_EOL);
		fwrite($file, "\t}".PHP_EOL);
		fwrite($file, PHP_EOL);
		fwrite($file, "}".PHP_EOL);
	}

	private function writeClassContent($file) {
		fwrite($file, "<?php".PHP_EOL);
        fwrite($file, "namespace Finder;".PHP_EOL);
		fwrite($file, "\tclass " . $this->name . " extends base" . $this->name . "{".PHP_EOL);
		fwrite($file, "".PHP_EOL);
		fwrite($file, "\t}".PHP_EOL);
	}

	public function __destroy() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
		unset($this);
	}

}