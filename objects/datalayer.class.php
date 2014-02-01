<?php
namespace Forge;
abstract class DataLayer {
    /**
     * @var bool $data_initialiased
     */
    protected $init = false;
    /**
     * @var array $fieldList
     */
    protected $fields = array();
    /**
     * @var int $recordVersion
     */
    protected $_recordVersion = 0;
    /**
     * @var bool $isNewRecord
     */
    protected $_new = true;
    /**
     * @var null $validationErrors
     */
    protected $_errors = null;

    const STATE_NEW = true;
    const STATE_LOADED = false;

    /**
     * Toggle initialise state of this data object
     * @param bool $state
     */
    public function init($state = false) {
        $this->init = $state;
    }

    /**
     * Upon construction, apply all specified rules
     */
    public function __construct(){
        $rules = $this->_rules();
        foreach($rules as $field => $definition){
            settype($this->$field,$this->_baseType($definition['type']));
            $this->fields[$field] = false; //set field as unchanged
            if($definition['default'] !== null) $this->$field = $definition['default'];
            else $this->$field = null; //init at null if default null
        }
    }
    /**
     * Magic function to set a variable, first it gets checked of rules, and then it'll be added if all is ok
     * @param $key
     * @param $val
     */
    public function __set($key,$val){
        $rules = $this->_retrieveRulesFor($key);
        if($val === null){ //check null allowed
            if($rules['allowNull'] == false) throw new \InvalidArgumentException(sprintf('Null is not allowed for %s->%s',get_class($this),$key));
            else $this->$key = $val;
        }
        else{
            if(isset($rules['length']['min']) && strlen((string)$val) < $rules['length']['min']) throw new \InvalidArgumentException(sprintf('Passed value is to short for %s->%s. Value needs to be at least %s characters long. "%s" was passed (%s long).', get_class($this),$key, $rules['length']['min'], (string)$val, strlen($val)));
            if(isset($rules['length']['max']) && strlen((string)$val) > $rules['length']['max']) throw new \InvalidArgumentException(sprintf('Passed value is to long for %s->%s. Value can not be longer than %s characters. "%s" was passed (%s long).', get_class($this),$key, $rules['length']['max'], (string)$val, strlen($val)));

            switch($rules['type']){
                case ObjectGenerator::FIELD_TYPE_STRING:
                    $this->_validateStringInput($key, $val);
                    break;
                case ObjectGenerator::FIELD_TYPE_LIST:
                    $this->_validateSerializedInput($key, $val);
                    break;
                case ObjectGenerator::FIELD_TYPE_BOOLEAN:
                    $this->_validateBooleanInput($key, $val);
                    break;
                case ObjectGenerator::FIELD_TYPE_INTEGER:
                    $this->_validateIntegerInput($key, $val);
                    break;
                case ObjectGenerator::FIELD_TYPE_FLOAT:
                    $this->_validateFloatInput($key, $val);
                    break;
                case ObjectGenerator::FIELD_TYPE_DATE:
                case ObjectGenerator::FIELD_TYPE_DATETIME:
                    $this->_validateDateTimeInput($key, $val);
                    break;
            }
        }
        return true;
	}

    /**
     * Magic Get function which transforms any database datetimes to correct objects
     * @param string $attributename
     * @return mixed
     */
    public function __get($key){
        $rules = $this->_retrieveRulesFor($key);

        if(!is_object($this->$key) && in_array($rules['type'],array(ObjectGenerator::FIELD_TYPE_DATE,ObjectGenerator::FIELD_TYPE_DATETIME))) {
            $dt = new \DateTime();
            return $dt->setTimestamp($this->$key);
        }
		return $this->$key;
	}

    /**
	 * Get an array of object fields
	 * @return array
	 */
    public function getFields() {
        return $this->fields;
    }

    /**
	 * Check if record is new or not.
	 * @return Boolean
	 */
    public function isNew() {
        return $this->_new;
    }

    /**
     * Retrieve the occured errors upon validation
     */
    public function getErrors(){
        return $this->_errors;
    }

    /**
	 * Check if specified property exists in this object
	 * @param String $property
	 * @return Boolean
	 */
	public function hasProperty($property){
		return (array_key_exists($property,get_object_vars($this)));
	}

    public function validate($clear_errors = true){
        $rules = $this->_rules();
        if($clear_errors === true) $this->_errors = array();
        foreach($rules as $field => $definition){
            if(isset($definition['allowNull']) && $definition['allowNull'] == false && is_null($this->$field)) $this->_errors[] = "Null is not allowed for ".get_class($this).'->'.$field;
            if(isset($definition['length'])){
                if(isset($definition['length']['min']) && $definition['length']['min'] > strlen((string)($this->$field))) $this->_errors[] = "Value for ".get_class($this).'->'.$field." is to short, needs to be at least ".$definition['length']['min']." long";
                if(isset($definition['length']['max']) && $definition['length']['max'] < strlen((string)($this->$field))) $this->_errors[] = "Value for ".get_class($this).'->'.$field." is to long, may to be a maximum of ".$definition['length']['max']." long";
            }
        }
        return empty($this->_errors);
    }

    /**
     * Set state of the dataobject
     * Possible entries:
     *  - self::STATE_NEW
     *  - self::STATE_LOADED
     * @param boolean $state
     */
    public function state($state = STATE_LOADED) {
        $this->_new = $state;
    }

    /**
     * Is passed class a instance of this base class
     * @param string $class_name
     * @return bool
     */
    public static function is_a($class_name){
		return (__CLASS__ == $class_name) ? true : false;
	}

    /**
     * @return array($rules)
     */
    abstract protected function _rules();

    /**
     * Public destroy method
     */
    public function __destroy(){
		foreach($this as $key => $value) unset($this->$key);
		unset($this);
	}

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param string $val
     * @throws \InvalidArgumentException
     */
    protected function _validateStringInput($key, $val){
        if(is_string($val) || (string)$val == $val){
            if(!$this->init) $this->fields[$key] = true;
            $this->$key = (string)$val;
        }
        else{
            throw new \InvalidArgumentException('Expected string for '.get_class($this).'->'.$key.'. Received "'.gettype($val).'"');
        }
    }

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param string $val
     * @throws \InvalidArgumentException
     */
    protected function _validateSerializedInput($key, $val){
        if((is_string($val) || (string)$val == $val) && @unserialize($val) !== false){
            if(!$this->init) $this->fields[$key] = true;
            $this->$key = (string)$val;
        }
        else{
            throw new \InvalidArgumentException('Expected serialized string for '.get_class($this).'->'.$key.'. Received "'.gettype($val).'" with value "'.$val.'"');
        }
    }

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param bool $val
     * @throws \InvalidArgumentException
     */
    protected function _validateBooleanInput($key, $val){
        if($val === "") throw new \InvalidArgumentException('Expected boolean for '.get_class($this).'->'.$key.'. Received empty string');
        if(is_bool($val) || $val === 0 || $val === 1){
            if(!is_bool($val)) $val = ($val == 0)?false:true;
            if(!$this->init) $this->fields[$key] = true;
            $this->$key = (int)($val);
        }
        else{
            throw new \InvalidArgumentException('Expected boolean for '.get_class($this).'->'.$key.'. Received "'.gettype($val).'"');
        }
    }

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param int $val
     * @throws \InvalidArgumentException
     */
    protected function _validateIntegerInput($key, $val){
        if($val === "") throw new \InvalidArgumentException('Expected Integer for '.get_class($this).'->'.$key.'. Received empty string');
        if(is_int($val) || ctype_digit($val) || (is_numeric($val) && intval($val) == $val && $val !== false)){
            $this->fields[$key] = true;
            $this->$key = (int)$val;
        }
        else{
            throw new \InvalidArgumentException('Expected integer for '.get_class($this).'->'.$key.'. Received "'.gettype($val).'"');
        }
    }

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param float $val
     * @throws \InvalidArgumentException
     */
    protected function _validateFloatInput($key, $val){
        if($val === "") throw new \InvalidArgumentException('Expected float for '.get_class($this).'->'.$key.'. Received empty string');
        if(is_float($val) || is_numeric($val) ){
            if(!$this->init) $this->fields[$key] = true;
            $this->$key = (float)$val;
        }
        else{
            throw new \InvalidArgumentException('Expected float for '.get_class($this).'->'.$key.'. Received "'.gettype($val).'"');
        }
    }

    /**
     * Validate the input type of the specified field
     * @param string $key
     * @param mixed $val
     * @throws \InvalidArgumentException
     */
    protected function _validateDateTimeInput($key, $val){
        if($val instanceOf \DateTime){
            if(!$this->init) $this->fields[$key] = true;
            $this->$key = $val;
        }
        else{
            throw new \InvalidArgumentException('Expected valid datetime object for '.get_class($this).'->'.$key.'. Received "'.$val.'"');
        }
    }

    /**
     * Validate the supported data type
     * @param string $type
     * @return string
     */
    protected function _baseType($type){
        switch($type){
            case ObjectGenerator::FIELD_TYPE_LIST:
                return 'string';
            case ObjectGenerator::FIELD_TYPE_DATE:
            case ObjectGenerator::FIELD_TYPE_DATETIME:
                return 'int';
            default:
                return $type;
        }
    }

    /**
     * @param string $field
     * @return array $rules
     * @throws \InvalidArgumentException
     */
    protected function _retrieveRulesFor($field){
        $rules = $this->_rules();
        if(isset($rules[$field])) return $rules[$field];
        else{
            if(isset($this->$field)) return array();
            else throw new \InvalidArgumentException('Trying to set a property that does not exist: '.get_class($this).'->'.$field);
        }
    }

}
?>