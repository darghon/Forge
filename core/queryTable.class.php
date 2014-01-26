<?php
namespace Forge;

class QueryTable{
    private $table = null;
    private $tableNamespace = null;
    private $joinMethod = null;
    private $joinOn = null;

    public function __construct($table, $tableNamespace = null, $joinOn = null, $joinMethod = null){
        $this->table = $table;
        $this->tableNamespace = $tableNamespace;
        $this->joinOn = $joinOn;
        $this->joinMethod = $joinMethod;
    }

    public function __set($key, $value){ $this->$key = $value; }
    public function __get($key){ return $this->$key; }

    public function __toString(){
        if($this->joinMethod === null){
            return $this->tableNamespace !== null ? $this->table.' '.$this->tableNamespace : $this->table;
        }
        else{
            if($this->joinMethod == Query::JOIN_UNION){
                return $this->joinMethod.' '.($this->tableNamespace !== null ? $this->table.' '.$this->tableNamespace : $this->table);
            }
            else{
                return $this->joinMethod.' join '.($this->tableNamespace !== null ? $this->table.' '.$this->tableNamespace : $this->table).($this->joinOn !== null ? ' on '.$this->joinOn : ' ');
            }
        }
    }

    public function __destroy(){ unset($this->table, $this->tableNamespace, $this->joinOn, $this->joinMethod); }
}