<?php
namespace Forge\Builder;

class DatabaseTable extends \Forge\baseGenerator
{

    protected $name = null;
    protected $fields = null;
    protected $links = null; /* Needed for index generations */
    protected $sql = null;
    protected $index = array();
    protected $translate = null;
    protected $multi_lang = false;
    protected $overwrite = false;

    public function __construct($args = array())
    {
        list($this->name, $this->fields, $this->links, $this->translate, $this->overwrite) = $args + array(null, array(), array(), array(), false);
        if (is_array($this->translate) && !empty($this->translate))
            $this->multi_lang = true;

        if (\Forge\Forge::Connection() == null) {
            trigger_error('No database connection has been specified.');
        }
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setLinks($links)
    {
        $this->links = $links;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function getSql()
    {
        //if($this->sql == null){ $this->generate(); }
        return $this->sql;
    }

    public function generate()
    {

        $db = &\Forge\Database::getDB();

        if ($this->overwrite === true) {
            $db->setQuery(sprintf('DROP TABLE IF EXISTS %s;', $db->getPrefix() . $this->name));
            $db->execute();
        }

        $this->sql = "";
        //$this->sql .= "/* Construction statement for ".$this->name." table. */\n";
        $this->sql .= "CREATE TABLE IF NOT EXISTS " . $db->getPrefix() . $this->name . "( ";
        foreach ($this->fields as $field) {
            $this->sql .= $this->parseField($field) . ", ";
        }
        $this->sql .= "PRIMARY KEY(ID)) ";
        $this->sql .= "ENGINE = INNODB; ";

        $db->setQuery($this->sql);
        $db->execute();

        foreach ($this->links as $link) {
            if (!$db->hasIndex($db->getPrefix() . $this->name, 'idx_' . $this->name . '_fk_' . $link['name'])) {
                $db->queueQuery(sprintf('CREATE INDEX %s ON %s (%s);', 'idx_' . $this->name . '_fk_' . $link['name'], $db->getPrefix() . $this->name, $link['local']));
            }
        }

        if (is_array($this->translate) && !empty($this->translate)) {
            //register the translation handlers
            \Forge\Generator::getInstance()->build('databasetable', array($this->name . '_i18n', $this->translate, array(), array(), $this->overwrite));
        }
    }


    private function parseField($field)
    {
        $sql = "`" . $field["name"] . "`";
        switch ($field["type"]) {
            case 'string':
                if ($field["length"] > 0) {
                    $sql .= " VARCHAR(" . $field["length"] . ")";
                } else {
                    $sql .= " TEXT";
                }
                break;
            case 'integer':
                if ($field["length"] < 1) {
                    $sql .= " BIGINT";
                } elseif ($field["length"] < 3) {
                    $sql .= " TINYINT(" . $field["length"] . ")";
                } elseif ($field["length"] < 12) {
                    $sql .= " INT(" . $field["length"] . ")";
                } else {
                    $sql .= " BIGINT(" . $field["length"] . ")";
                }
                break;
            case 'boolean':
                $sql .= " TINYINT(1)";
                break;
            //Changing all date representation to the UTC standard (unix_timestamp stored as int)
            case 'date':
            case 'time':
            case 'datetime':
                $sql .= " INT(11)";
                break;
            case 'double':
            case 'float':
            case 'decimal':
                $nums = explode(".", $field["length"]);
                $sql .= " DECIMAL(" . (array_sum($nums) + 1) . "," . $nums[count($nums) - 1] . ")";
                break;
            default:
                $sql .= " TEXT";
                break;
        }

        if ($field["default"] != 'null') {
            $sql .= " DEFAULT '" . (($field["default"] === "true") ? "1" : (($field["default"] === "false") ? "0" : $field["default"])) . "'";
        }
        if ($field["null"] == false) {
            $sql .= " NOT NULL";
        }
        if ($field["name"] == "ID") {
            $sql .= " AUTO_INCREMENT";
        }
        return $sql;
    }

    public function getIndexes()
    {
        return $this->index;
    }

    public function __destroy()
    {
        foreach ($this as $key => $value) unset($this->$key);
        unset($this);
    }

}
