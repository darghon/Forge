<?php
namespace Forge;

class ColumnDefinition
{

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'double';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_LIST = 'list';

    static $validFieldTypes = [
        ColumnDefinition::TYPE_STRING,
        ColumnDefinition::TYPE_INTEGER,
        ColumnDefinition::TYPE_FLOAT,
        ColumnDefinition::TYPE_BOOLEAN,
        ColumnDefinition::TYPE_DATE,
        ColumnDefinition::TYPE_DATETIME,
        ColumnDefinition::TYPE_LIST
    ];

    /** @var string */
    protected $_name;
    /** @var string */
    protected $_type = self::TYPE_STRING;
    /** @var integer */
    protected $_length;
    /** @var integer */
    protected $_decimals = 0;
    /** @var mixed */
    protected $_default;
    /** @var boolean */
    protected $_null;
    /** @var boolean */
    protected $_translated;

    protected $_defaults = [
        self::TYPE_INTEGER =>   ['length' => 10, 'default' => null, 'null' => true ],
        self::TYPE_FLOAT =>     ['length' => 14, 'default' => null, 'null' => true, 'decimals' => 4 ],
        self::TYPE_DECIMAL =>   ['length' => 14, 'default' => null, 'null' => true, 'decimals' => 4 ],
        self::TYPE_BOOLEAN =>   ['length' => 1,  'default' => null, 'null' => true ],
        self::TYPE_DATE =>      ['length' => 10, 'default' => null, 'null' => true ],
        self::TYPE_DATETIME =>  ['length' => 20, 'default' => null, 'null' => true ],
        self::TYPE_STRING =>    ['length' => 0,  'default' => null, 'null' => true ],
        self::TYPE_LIST =>      ['length' => 0,  'default' => null, 'null' => true ],
    ];

    /**
     * @param null|string $columnName
     */
    public function __construct($columnName = null, $columnType = null)
    {
        if (!is_null($columnName)) $this->setName($columnName);
        if (!is_null($columnType)) $this->setType($columnType);
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     * @param mixed $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->_default = $default;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->_length;
    }

    /**
     * @param mixed $length
     *
     * @return $this
     */
    public function setLength($length)
    {
        $this->_length = $length;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param mixed $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNull()
    {
        return $this->_null;
    }

    /**
     * @param mixed $null
     *
     * @return $this
     */
    public function setNull($null)
    {
        $this->_null = $null;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->_type = $type;
        foreach($this->_defaults[$type] as $key => $value) if (is_null($this->{'_'.$key})) $this->{'set'.ucwords($key)}($value);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isTranslated()
    {
        return $this->_translated;
    }

    /**
     * @param boolean $translated
     *
     * @return $this
     */
    public function setTranslated($translated)
    {
        $this->_translated = !!$translated;
        return $this;
    }


}