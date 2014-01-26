<?php
namespace Forge;
//This class will receive a object, and will transform it into a sql statement to insert or delete
class Persister
{
    private $pref = null;
    private $object = false;
    private $method = false;
    private $fields = false;
    private $sql = false;

    public function __construct($prefix = null)
    {
        $this->pref = $prefix;
    }

    public function getSql(&$object)
    {
        $this->object =& $object;
        $this->fields = $this->object->getFields();
        $this->process();
        return $this->sql;
    }

    private function process()
    {
        switch ($this->object->isNew()) {
            case true: //Insert
                $this->createInsertStatement();
                break;
            default: //Update
                $this->createUpdateStatement();
                break;
        }
    }

    private function createInsertStatement()
    {
        $this->method = "Insert";
        $this->sql = "Insert into ";
        $this->sql .= $this->pref . $this->getTableName();
        $tmpsql = array();
        foreach ($this->fields as $key => $field) {
            if ($key == 'ID') continue;
            $tmpsql[] = "`" . $key . "`";
        }
        $this->sql .= "(" . implode(",", $tmpsql) . ")";
        $this->sql .= " values(";
        $tmpsql = array();
        foreach ($this->fields as $key => $field) {
            if ($key == 'ID') continue;
            if($this->object->$key === null) $tmpsql [] = "null";
            else $tmpsql[] = "'" . Database::escape($this->object->$key) . "'";
        }
        $this->sql .= implode(",", $tmpsql);
        $this->sql .= ")";
    }

    protected function getTableName()
    {
        return substr(get_class($this->object), strrpos(get_class($this->object), '\\') + 1);
    }

    private function createUpdateStatement()
    {
        $this->method = "Update";
        $this->sql = "Update ";
        //get table name from tag (tag: DUser)
        $this->sql .= $this->pref . $this->getTableName();
        $tmpsql = array();
        foreach ($this->fields as $key => $field) {
            if (!$field) continue; //If field is false, then do nothing, go to next field
            if ($key == "_recordVersion" || $key == "ID") continue; //always skip record version and ID
            $tmpsql[] = "`" . $key . "`" . " = " . ($this->object->$key === null ? "null" : "'".Database::escape($this->object->$key) . "'");
        }
        if (count($tmpsql) > 0) {
            $tmpsql[] = "_recordVersion='" . (int)($this->object->_recordVersion + 1) . "'";
            $this->sql .= " Set " . implode(", ", $tmpsql);
            $this->sql .= " where ID=\"" . $this->object->ID . "\" and _recordVersion <= \"" . $this->object->_recordVersion . "\";";
        } else {
            $this->sql = ""; //no statement if no fields where updated
        }
    }

    public function getSmallSql(&$object)
    {
        $sql = "(";
        $tmpsql = array();
        $fields = $object->getFields();
        foreach ($fields as $key => $field) {
            if ($key == 'ID') continue;
            $tmpsql[] = '"' . Database::escape($object->$key) . '"';
        }
        $sql .= implode(',', $tmpsql) . ")";
        return $sql;
    }

    public function __toString()
    {
        return (string)$this->sql;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function __destroy()
    {
        unset($this->object, $this->method, $this->fields, $this->sql);
    }
}