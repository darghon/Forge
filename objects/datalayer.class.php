<?php
namespace Forge;

abstract class DataLayer
{
    const STATE_NEW = true;
    const STATE_LOADED = false;
    /**
     * @var bool $data_initialiased
     */
    protected $init = false;
    /**
     * @var array $fieldList
     */
    protected $fields = [];
    /**
     * @var bool $isNewRecord
     */
    protected $_new = true;
    /**
     * @var null $validationErrors
     */
    protected $_errors = null;

    /**
     * Upon construction, apply all specified rules
     */
    public function __construct()
    {
        $rules = $this->_rules();
        foreach ($rules as $field => $definition) {
            settype($this->$field, $this->_baseType($definition['type']));
            $this->fields[$field] = false; //set field as unchanged
            if ($definition['default'] !== null) $this->$field = $definition['default'];
            else $this->$field = null; //init at null if default null
        }
    }

    /**
     * @return array($rules)
     */
    abstract protected function _rules();

    /**
     * Validate the supported data type
     *
     * @param string $type
     *
     * @return string
     */
    protected function _baseType($type)
    {
        switch ($type) {
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
     * Is passed class a instance of this base class
     *
     * @param string $class_name
     *
     * @return bool
     */
    public static function is_a($class_name)
    {
        return (__CLASS__ == $class_name) ? true : false;
    }

    /**
     * Toggle initialise state of this data object
     *
     * @param bool $state
     */
    public function init($state = false)
    {
        $this->init = $state;
    }

    /**
     * Magic Get function which transforms any database datetimes to correct objects
     *
     * @param string $attributename
     *
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->$key === null) return null;

        $rules = $this->_retrieveRulesFor($key);

        if (!is_object($this->$key) && in_array($rules['type'], [ObjectGenerator::FIELD_TYPE_DATE, ObjectGenerator::FIELD_TYPE_DATETIME])) {
            $dt = new \DateTime();
            $dt->setTimestamp($this->$key);
            $dt->setTimezone(new \DateTimeZone(Forge::Translate()->getTimeZone()));

            return $dt;
        }

        return $this->$key;
    }

    /**
     * Magic function to set a variable, first it gets checked of rules, and then it'll be added if all is ok
     *
     * @param $key
     * @param $val
     */
    public function __set($key, $val)
    {
        $rules = $this->_retrieveRulesFor($key);
        if ($val === null) { //check null allowed
            if ($rules['allowNull'] == false) throw new \InvalidArgumentException(sprintf('Null is not allowed for %s->%s', get_class($this), $key));
            else $this->$key = $val;
        } else {
            if (isset($rules['length']['min']) && !is_object($val) && strlen((string)$val) < $rules['length']['min']) throw new \InvalidArgumentException(sprintf('Passed value is to short for %s->%s. Value needs to be at least %s characters long. "%s" was passed (%s long).', get_class($this), $key, $rules['length']['min'], (string)$val, strlen($val)));
            if (isset($rules['length']['max']) && !is_object($val) && strlen((string)$val) > $rules['length']['max']) throw new \InvalidArgumentException(sprintf('Passed value is to long for %s->%s. Value can not be longer than %s characters. "%s" was passed (%s long).', get_class($this), $key, $rules['length']['max'], (string)$val, strlen($val)));

            switch ($rules['type']) {
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
     * @param string $field
     *
     * @return array $rules
     * @throws \InvalidArgumentException
     */
    protected function _retrieveRulesFor($field)
    {
        $rules = $this->_rules();
        if (isset($rules[$field])) return $rules[$field];
        else {
            if (isset($this->$field)) return [];
            else throw new \InvalidArgumentException('Trying to set a property that does not exist: ' . get_class($this) . '->' . $field);
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param string $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateStringInput($key, $val)
    {
        if (is_string($val) || (string)$val == $val) {
            if (!$this->init) $this->fields[$key] = true;
            $this->$key = (string)$val;
        } else {
            throw new \InvalidArgumentException('Expected string for ' . get_class($this) . '->' . $key . '. Received "' . gettype($val) . '"');
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param string $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateSerializedInput($key, $val)
    {
        if ((is_string($val) || (string)$val == $val) && @unserialize($val) !== false) {
            if (!$this->init) $this->fields[$key] = true;
            $this->$key = (string)$val;
        } else {
            throw new \InvalidArgumentException('Expected serialized string for ' . get_class($this) . '->' . $key . '. Received "' . gettype($val) . '" with value "' . $val . '"');
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param bool   $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateBooleanInput($key, $val)
    {
        if ($val === "") throw new \InvalidArgumentException('Expected boolean for ' . get_class($this) . '->' . $key . '. Received empty string');
        if (is_bool($val) || in_array($val, [0, 1, "0", "1"])) {
            if (!is_bool($val)) $val = (intval($val) == 0) ? false : true;
            if (!$this->init) $this->fields[$key] = true;
            $this->$key = (int)($val);
        } else {
            throw new \InvalidArgumentException('Expected boolean for ' . get_class($this) . '->' . $key . '. Received "' . gettype($val) . '"');
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param int    $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateIntegerInput($key, $val)
    {
        if ($val === "") throw new \InvalidArgumentException('Expected Integer for ' . get_class($this) . '->' . $key . '. Received empty string');
        if (is_int($val) || ctype_digit($val) || (is_numeric($val) && intval($val) == $val && $val !== false)) {
            $this->fields[$key] = true;
            $this->$key = (int)$val;
        } else {
            throw new \InvalidArgumentException('Expected integer for ' . get_class($this) . '->' . $key . '. Received "' . gettype($val) . '"');
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param float  $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateFloatInput($key, $val)
    {
        if ($val === "") throw new \InvalidArgumentException('Expected float for ' . get_class($this) . '->' . $key . '. Received empty string');
        if (is_float($val) || is_numeric($val)) {
            if (!$this->init) $this->fields[$key] = true;
            $this->$key = (float)$val;
        } else {
            throw new \InvalidArgumentException('Expected float for ' . get_class($this) . '->' . $key . '. Received "' . gettype($val) . '"');
        }
    }

    /**
     * Validate the input type of the specified field
     *
     * @param string $key
     * @param mixed  $val
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateDateTimeInput($key, $val)
    {
        if ($val instanceOf \DateTime) {
            if (!$this->init) $this->fields[$key] = true;
            $this->$key = $val;
        } else {
            //might be a timestamp
            if (intval($val) > 10) {
                if (!$this->init) $this->fields[$key] = true;
                $this->$key = new \DateTime();
                $this->$key->setTimestamp(intval($val));
                $this->$key->setTimezone(new \DateTimeZone(Forge::Translate()->getTimeZone()));
            } else {
                throw new \InvalidArgumentException('Expected valid datetime object for ' . get_class($this) . '->' . $key . '. Received "' . $val . '"');
            }
        }
    }

    /**
     * Retrieve the validation rules for a specific field.
     * Rules are publicly available for automatic validation creation
     *
     * @param string|null $field
     *
     * @return array $rules
     */
    public function getValidationRules($field = null)
    {
        if ($field === null) {
            return $this->_rules();
        } else {
            $rules = $this->_rules();
            if (array_key_exists($field, $rules)) return $rules[$field];
        }

        return [];
    }

    /**
     * Get an array of object fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Check if record is new or not.
     *
     * @return Boolean
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Retrieve the occured errors upon validation
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Check if specified property exists in this object
     *
     * @param String $property
     *
     * @return Boolean
     */
    public function hasProperty($property)
    {
        return (array_key_exists($property, get_object_vars($this)));
    }

    public function validate($clear_errors = true)
    {
        $rules = $this->_rules();
        if ($clear_errors === true) $this->_errors = [];
        foreach ($rules as $field => $definition) {
            if (isset($definition['allowNull']) && $definition['allowNull'] == false && is_null($this->$field)) $this->_errors[] = "Null is not allowed for " . get_class($this) . '->' . $field;
            if (isset($definition['length'])) {
                if (isset($definition['length']['min']) && !is_object($this->$field) && $definition['length']['min'] > strlen((string)($this->$field))) $this->_errors[] = "Value for " . get_class($this) . '->' . $field . " is to short, needs to be at least " . $definition['length']['min'] . " long";
                if (isset($definition['length']['max']) && !is_object($this->$field) && $definition['length']['max'] < strlen((string)($this->$field))) $this->_errors[] = "Value for " . get_class($this) . '->' . $field . " is to long, may to be a maximum of " . $definition['length']['max'] . " long";
            }
        }

        return empty($this->_errors);
    }

    /**
     * Set state of the dataobject
     * Possible entries:
     *  - self::STATE_NEW
     *  - self::STATE_LOADED
     *
     * @param boolean $state
     */
    public function state($state = self::STATE_LOADED)
    {
        $this->_new = $state;
    }

    /**
     * Public destroy method
     */
    public function __destroy()
    {
        foreach ($this as $key => $value) unset($this->$key);
        unset($this);
    }

}

?>