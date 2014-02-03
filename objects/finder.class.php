<?php
namespace Forge;
abstract class Finder {

	/**
	 * This object is the singleton instance of the DBHandler object
	 * @var DatabaseHandler
	 */
	protected $db = null;

	public function __construct() {
		$this->db = &Database::getDB();
	}

	public function & createInstance($class) {
		$obj = new $class();
		$obj->setID(0);
		return $obj;
	}
	
	public function & createObject($row){
		return ObjectFactory::build($this->_getClassName(),$row);
	}

	public function _set($key, $value) {
		$this->$key = $value;
	}

	private function _pages($pagesize = 20) {
		$this->db->setQuery(Query::build()->select(array('num' => 'COUNT(ID)'))->from($this->getTableName())->where('_deletedAt IS NULL'));
		$this->db->execute();
		$row = $this->db->getRecord();
		return ceil($row['num'] / $pagesize);
	}

	private function _persist(&$obj) {
		//create a persistance object
		//This object will only form the needed sql statement
		$sql = new Persister($this->db->getPrefix());

        if (get_class($obj) == 'Data\\' . $this->_getClassName() && $this->validate() && $obj->validate()) {
            $this->db->setQuery($sql->getSql($obj));
            if ($this->db->execute()) {
                if ($sql->getMethod() == 'Insert') {
                    $obj->ID = $this->db->getLastInsertID();
                    //alter state
                    $obj->state(DataLayer::STATE_LOADED);
                } else {
                    if ($this->db->getAffectedRows() == 0)
                        throw new Exception("Update failed: " . $sql->getSql($obj));
                    $obj->_recordVersion++;
                }
            }
        }

		return true;
	}

	private function _delete(DataLayer &$obj) {
        //make sure the data object is passed to the correct database finder
        if (get_class($obj) == 'Data\\' .  $this->_getClassName()) {
            $this->db->setQuery(
                sprintf('UPDATE %s SET _deletedAt = %s WHERE ID = "%s" AND _recordVersion <= "%s";',
                        $this->getTableName(),
                        time(),
                        $obj->ID,
                        $obj->_recordVersion
                )
            );
            return $this->db->execute();
        } else {
            return false;
        }
	}

	public function & byID($id) {
		$def = false;
		$return = Forge::getObject(array_pop(explode('\\',get_class($this))), $id);
		if ($return === false) {
			$this->db->setQuery(Query::build()->select()->from($this->getTableName())->where('ID',$id));
			$this->db->execute();
			if ($this->db->hasRecords()) {
				$row = $this->db->getRecord();
				return $this->createObject($row);
			} else {
				return $def;
			}
		} else {
			return $return;
		}
	}

	public function & all($page = false, $pagesize = 20) {
		$return = array();
        Query::build()->select()->from($this->getTableName())->where('_deletedAt IS NULL');
		$this->db->setQuery(sprintf("SELECT * FROM %s%s WHERE _deletedAt IS NULL %s;", $this->db->getPrefix(),  array_pop(explode('\\',get_class($this))), ($page !== false ? " Limit " . (($page - 1) * $pagesize) . "," . $pagesize : "")));
		$this->db->execute();
		if ($this->db->hasRecords()) {
			while ($row = $this->db->getRecord()) {
				$return[] = &$this->createObject($row);
			}
		}
		return $return;
	}

	public function getPages($pagesize = 20) {
		return $this->_pages($pagesize);
	}

	public function persist(&$obj) {
        $success = true;
        if (is_array($obj)) {
            foreach ($obj as &$o) {
                $this->_persist($o);
            }
        }
        else {
            $this->_persist($obj);
        }
		return $success;
	}

    /**
     * Delete a object
     * @param DataLayer $obj
     * @return bool $success
     */
    public function delete(DataLayer &$obj) {
        $success = true;
        if (is_array($obj)) {
            foreach ($obj as &$o) {
                if( !$success ) continue; //skip others when not successful
                $this->_delete($o);
            }
        } else {
            $success = $this->_delete($obj);
        }

		return $success;
	}

	public function __destroy() {
		foreach ($this as $key => $value)
			unset($this->$key);
		unset($this);
	}
	
	public static function is_a($class_name){
		return (__CLASS__ == $class_name) ? true : false;
	}
	
	public function getTableName(){
		return $this->db->getPrefix(). $this->_getClassName();
	}

    /**
     * @return mixed
     */
    protected function _getClassName() {
        $explode = explode('\\', get_class($this));
        return array_pop($explode);
    }

}

?>