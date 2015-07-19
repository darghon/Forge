<?php
namespace Forge;

//This class will receive a object, and will transform it into a sql statement to insert or delete
class Persister
{
    private $pref = null;
    /**
     * @var DataLayer
     */
    private $object = false;
    private $method = false;
    private $fields = [];
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
        $this->sql = "INSERT INTO ";
        $this->sql .= $this->pref . $this->getTableName();
        $tmpsql = [];
        foreach ($this->fields as $key => $field) {
            if ($key == 'id') continue;
            $tmpsql[] = "`" . $key . "`";
        }
        $this->sql .= "(" . implode(",", $tmpsql) . ")";
        $this->sql .= " VALUES(";
        $tmpsql = [];
        foreach ($this->fields as $key => $field) {
            if ($key == 'id') continue;
            if ($this->object->$key === null) $tmpsql [] = "null";
            else $tmpsql[] = "'" . $this->getValue($this->object, $key) . "'";
        }
        $this->sql .= implode(",", $tmpsql);
        $this->sql .= ")";
    }

    protected function getTableName()
    {
        return Tools::camelcasetostr(substr(get_class($this->object), strrpos(get_class($this->object), '\\') + 1));
    }

    protected function getValue($object, $key)
    {
        $value = $this->object->$key;
        if ($value instanceOf \DateTime) {
            //Always save UTC Time
            $value->setTimezone(new \DateTimeZone('UTC'));
            $returnValue = $value->getTimestamp();
            //reset the timezone
            $value->setTimezone(new \DateTimeZone(\Forge\Forge::Translate()->getTimeZone()));

            return $returnValue;
        }

        return Database::escape($value);
    }

    private function createUpdateStatement()
    {
        $this->method = "Update";
        $this->sql = "UPDATE ";
        //get table name from tag (tag: DUser)
        $this->sql .= $this->pref . $this->getTableName();
        $tmpsql = [];
        foreach ($this->fields as $key => $field) {
            if (!$field) continue; //If field is false, then do nothing, go to next field
            if ($key == "_record_version" || $key == "ID") continue; //always skip record version and ID
            $tmpsql[] = "`" . $key . "`" . " = " . ($this->object->$key === null ? "null" : "'" . $this->getValue($this->object, $key) . "'");
        }
        if (count($tmpsql) > 0) {
            $tmpsql[] = "_record_version='" . (int)($this->object->_record_version + 1) . "'";
            $this->sql .= " SET " . implode(", ", $tmpsql);
            $this->sql .= " WHERE id=\"" . $this->object->id . "\" and _record_version <= \"" . $this->object->_record_version . "\";";
        } else {
            $this->sql = ""; //no statement if no fields where updated
        }
    }

    public function getSmallSql(&$object)
    {
        $sql = "(";
        $tmpsql = [];
        $fields = $object->getFields();
        foreach ($fields as $key => $field) {
            if ($key == 'ID') continue;
            $tmpsql[] = '"' . $this->getValue($object, $key) . '"';
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