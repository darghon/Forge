<?php
namespace Forge\Builder;

class Sql extends \Forge\baseGenerator
{

    private $name = null;
    private $fields = null;
    private $links = null; /* Needed for index generations */
    private $sql = null;
    private $index = array();

    public function __construct($name = null, $fields = null)
    {
        $this->name = $name;
        $this->fields = $fields;
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
        $this->sql = "";
        $this->sql .= "/* Construction statement for " . $this->name . " table. */\n";
        $this->sql .= "CREATE TABLE " . \Forge\Database::getDB()->getPrefix() . $this->name . "(\n";
        foreach ($this->fields as $field) {
            $this->sql .= $this->parseField($field) . ",\n";
        }
        $this->sql .= "PRIMARY KEY(ID))\n";
        $this->sql .= "ENGINE = INNODB;\n\n";

        foreach ($this->links as $link) {
            $this->index[] = sprintf('CREATE INDEX %s ON %s (%s);', 'idx_' . $this->name . '_fk_' . $link['name'], \Forge\Database::getDB()->getPrefix() . $this->name, $link['local']);
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
            case 'date':
                $sql .= " DATE";
                break;
            case 'time':
                $sql .= " TIME";
                break;
            case 'timestamp':
            case 'datetime':
                $sql .= " DATETIME";
                break;
            case 'double':
                $nums = explode(".", $field["length"]);
                $sql .= " DECIMAL(" . (array_sum($nums) + 1) . "," . $nums[count($nums) - 1] . ")";
                break;
            case 'list':
                $sql .= " LONGTEXT";
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
        unset($this->name, $this->fields, $this->location);
    }

}