<?php
namespace Forge\Builder;

class BusinessLayer extends \Forge\baseGenerator {

	protected $name = null;
	protected $fields = null;
	protected $links = null;
	protected $translate = null;
    protected $extends = null;
    protected $implements = null;
	protected $location = null;
	protected $multi_lang = false;

	public function __construct($args = array()) {
		list($this->name, $this->fields, $this->links, $this->translate, $this->extends, $this->implements) = $args + array(null, array(), array(), array(), null, null);
		$this->location = \Forge\Config::path('objects') . '/business/';
		if (is_array($this->translate) && !empty($this->translate))
			$this->multi_lang = true;
        if($this->extends == null || $this->extends == '~') $this->extends = '\Forge\BusinessLayer';
        else{
            if(!class_exists($this->extends)) throw new \InvalidArgumentException('Trying to extend a class in '.$this->name.' that does not exist('.$this->extends.').');
            else{
                $test = $this->extends;
                if(!$test::is_a('Forge\\BusinessLayer')) throw new \InvalidArgumentException('Trying to extend a class in '.$this->name.' that does not extend BusinessLayer('.$this->extends.').');
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

	public function setLinks($links) {
		$this->links = $links;
	}

	public function getLinks() {
		return $this->links;
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

		//genarate class is not exist (need to preserve user code)
		if (!file_exists($this->location . $this->name . ".class.php")) {
			$file = fopen($this->location . $this->name . ".class.php", "w");
			$this->writeClassContent($file);
			fclose($file);
			echo "."; flush();
			chmod($this->location . $this->name . ".class.php",0777);
			echo "."; flush();
			unset($file);
		}

		if (is_array($this->translate) && !empty($this->translate)) {
			//register the translation handlers
            \Forge\Generator::getInstance()->build('businesslayer',array($this->name.'_i18n',$this->translate, $this->linkThis(), array()));
		}
	}
	
	private function linkThis(){
		return array(
			array(
				"name" => $this->name,
				"target" => $this->name,
				"local"=>$this->name."ID"
				)
			);
	}

	private function writeBaseContent($file) {
		fwrite($file, "<?php " . PHP_EOL);
		fwrite($file, "abstract class base" . $this->name . " extends ".$this->extends.$this->implements."{" . PHP_EOL);
		fwrite($file, "" . PHP_EOL);

		foreach ($this->fields as $field) {
            $functionName = \Forge\Tools::strtocamelcase($field["name"], true);
            switch($field['type']){
                case 'list':
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return array \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){" . PHP_EOL);
                    fwrite($file, "\t\t\$unserialize = unserialize(\$this->data->" . $field["name"] . ");" . PHP_EOL);
                    fwrite($file, "\t\treturn is_array(\$unserialize) ? \$unserialize : array();" . PHP_EOL);
                    fwrite($file, "\t}" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param array  \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(array \$val = array()){ \$this->data->" . $field["name"] . " = serialize(\$val); return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
                case 'timestamp':
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return integer \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){ return date(\"c\",\$this->data->" . $field["name"] . "); }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param array  \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(\$val){ \$this->data->" . $field["name"] . " = date('Y-m-d H:i:s',\$val); return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
                default:
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return " . $field["type"] . ' $' . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){ return \$this->data->" . $field["name"] . "; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param " . $field["type"] . ' $' . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(\$val){ \$this->data->" . $field["name"] . " = \$val; return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
            }
		}
		//generate links if needed
		if (is_array($this->links)) {
			foreach ($this->links as $link) {
				fwrite($file, "\t/**" . PHP_EOL);
				fwrite($file, "\t * Lazy loading function that retrieves the selected " . $link["name"] . "" . PHP_EOL);
				fwrite($file, "\t * @return " . $link["target"] . " " . PHP_EOL);
				fwrite($file, "\t */" . PHP_EOL);
				fwrite($file, "\tpublic function & get" . $link["name"] . "(){" . PHP_EOL);
				fwrite($file, "\t\treturn " . $link["target"] . "::Find(\$this->data->" . $link["local"] . ");" . PHP_EOL);
				fwrite($file, "\t}" . PHP_EOL);
				fwrite($file, "" . PHP_EOL);
			}
		}
        // Check if any translation fields are added to the configuration
		if ($this->multi_lang === true) {
            //Create the lazy loading function for translations
			fwrite($file, "\t/**" . PHP_EOL);
			fwrite($file, "\t * Lazy loading translation" . PHP_EOL);
			fwrite($file, "\t * @return " . $this->name . "_i18n " . PHP_EOL);
			fwrite($file, "\t */" . PHP_EOL);
			fwrite($file, "\tpublic function & getTranslation(\$lang = null){" . PHP_EOL);
			fwrite($file, "\t\tif(\$lang === null) \$lang = \Forge\Forge::Translate()->getActiveLanguage();" . PHP_EOL);
			fwrite($file, "\t\t\$lang = explode('_',\$lang);" . PHP_EOL);
			fwrite($file, "\t\treturn " . $this->name . "_i18n::Find()->getTranslationByID(\$this->data->ID,\$lang[0]);" . PHP_EOL);
			fwrite($file, "\t}" . PHP_EOL);
			fwrite($file, "" . PHP_EOL);
            //create a getter for each translated field
            foreach($this->translate as $translated_field){
                if(in_array($translated_field['name'],array('ID', $this->name.'ID', 'Lang', '_recordVersion', '_deletedAt'))) continue; //skip these fields
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Retrieve translated value for ".$translated_field['name'] . PHP_EOL);
                fwrite($file, "\t * @return " . $translated_field['type'] . " \$" . $translated_field['name'] . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function get".$translated_field['name']."(\$lang = null){" . PHP_EOL);
                fwrite($file, "\t\treturn \$this->getTranslation(\$lang)->get".$translated_field['name']."();" . PHP_EOL);
                fwrite($file, "\t}" . PHP_EOL);
                fwrite($file, "" . PHP_EOL);
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Set translated value for ".$translated_field['name'] . PHP_EOL);
                fwrite($file, "\t * @param string \$value" . PHP_EOL);
                fwrite($file, "\t * @param string \$lang" . PHP_EOL);
                fwrite($file, "\t * @return \$this" . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function set".$translated_field['name']."(\$value, \$lang = null){" . PHP_EOL);
                fwrite($file, "\t\t\$i18n = &\$this->getTranslation(\$lang);".PHP_EOL);
                fwrite($file, "\t\t\$i18n->set".$translated_field['name']."(\$value);" . PHP_EOL);
                fwrite($file, "\t\treturn \$this;".PHP_EOL);
                fwrite($file, "\t}" . PHP_EOL);
                fwrite($file, "" . PHP_EOL);
            }
		}
		//create a magic __toString method for select list dumping
		fwrite($file, "\t/**" . PHP_EOL);
		fwrite($file, "\t * Magic to string method" . PHP_EOL);
		fwrite($file, "\t * @return String " . PHP_EOL);
		fwrite($file, "\t */" . PHP_EOL);
		fwrite($file, "\tpublic function __toString(){" . PHP_EOL);
		$ok = false;
		foreach ($this->fields as $field) {
			if ($ok)
				continue;
			if (strtolower($field['name']) == 'name' || strtolower($field['name']) == 'description') {
				fwrite($file, "\t\treturn \$this->data->" . $field["name"] . ";" . PHP_EOL);
				$ok = true;
			}
		}
		if (!$ok) {
			if ($this->multi_lang === true) {
				fwrite($file, "\t\treturn (String)\$this->getTranslation();" . PHP_EOL);
			} else {
				fwrite($file, "\t\treturn (String)\$this->data->ID;" . PHP_EOL);
			}
		}
		fwrite($file, "\t}" . PHP_EOL);
		fwrite($file, "" . PHP_EOL);
		fwrite($file, "\t/**" . PHP_EOL);
		fwrite($file, "\t * Object validator, if this class returns true, the object can is valid and can be saved." . PHP_EOL);
		fwrite($file, "\t * @return Boolean " . PHP_EOL);
		fwrite($file, "\t */" . PHP_EOL);
		fwrite($file, "\tpublic function validate(){" . PHP_EOL);
		fwrite($file, "\t\treturn parent::validate();" . PHP_EOL);
		fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Final Static function that allows the retrieval of the correct Finder functions for this object" . PHP_EOL);
        fwrite($file, "\t * @return Finder\\".$this->getName() . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tfinal public static function & Find(\$id = null){" . PHP_EOL);
        fwrite($file, "\t\treturn parent::Find(\$id);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Public Destructor, unset every used valiable" . PHP_EOL);
        fwrite($file, "\t * @return String " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function __destroy(){" . PHP_EOL);
        fwrite($file, "\t\tforeach(\$this as \$key => \$val) unset(\$this->\$key);" . PHP_EOL);
        fwrite($file, "\t\tunset(\$this);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
		fwrite($file, "}" . PHP_EOL);
	}

	private function writeClassContent($file) {
		fwrite($file, "<?php" . PHP_EOL);
		fwrite($file, "\t/**" . PHP_EOL);
		fwrite($file, "\t * Forge Business class" . PHP_EOL);
		fwrite($file, "\t * --------------------" . PHP_EOL);
		fwrite($file, "\t * This class is the user control for ".$this->name. ".".PHP_EOL);
		fwrite($file, "\t * Any custom actions to this database object need to be specified here." . PHP_EOL);		
		fwrite($file, "\t * " . PHP_EOL);
		fwrite($file, "\t * @author Gerry Van Bael " . PHP_EOL);
		fwrite($file, "\t */" . PHP_EOL);
		fwrite($file, "\tclass " . $this->name . " extends base" . $this->name . "{" . PHP_EOL);
		fwrite($file, "" . PHP_EOL);
		fwrite($file, "\t}" . PHP_EOL);
	}

	public function __destroy() {
		unset($this->name, $this->fields, $this->location);
	}

}