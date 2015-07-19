<?php
namespace Forge;

/**
 * Forge Finder class
 * --------------------
 * This class is the Forge Finder Class, it contains the general find functions available in all Finder objects.
 *
 * @author Gerry Van Bael
 * @package Forge
 */
abstract class Finder
{

    /**
     * This object is the singleton instance of the DBHandler object
     *
     * @var DatabaseHandler
     */
    protected $db = null;


    public function __construct()
    {
        $this->setDb(Database::getDB());
    }

    /**
     * @return DatabaseHandler
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param DatabaseHandler $db
     *
     * @return $this
     */
    public function setDb(DatabaseHandler &$db)
    {
        $this->db = &$db;
        return $this;
    }
    
    public static function is_a($class_name)
    {
        return (__CLASS__ == $class_name) ? true : false;
    }

    public function & createInstance($class)
    {
        $obj = new $class();
        $obj->setID(0);

        return $obj;
    }

    public function _set($key, $value)
    {
        $this->$key = $value;
        return $this;
    }

    /**
     * @param $id
     *
     * @return bool|\Forge\BusinessLayer
     */
    public function & byID($id)
    {
        $def = false;
        $list = explode('\\', get_class($this));
        $return = &Forge::getObject(array_pop($list), $id);
        if ($return === false) {
            $this->getDb()->setQuery(Query::build()->select()->from($this->getTableName())->where('id', $id));
            $this->getDb()->execute();
            if ($this->getDb()->hasRecords()) {
                $row = $this->getDb()->getRecord();
                return $this->createObject($row);
            } else {
                return $def;
            }
        } else {
            return $return;
        }
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getDb()->getPrefix() . Tools::camelcasetostr($this->_getClassName());
    }

    /**
     * @return string
     */
    protected function _getClassName()
    {
        $explode = explode('\\', get_class($this));

        return array_pop($explode);
    }

    /**
     * @param $row
     *
     * @return \Forge\BusinessLayer
     */
    public function & createObject($row)
    {
        return ObjectFactory::build($this->_getClassName(), $row);
    }

    /**
     * @param bool $page
     * @param int  $pagesize
     *
     * @return \Forge\BusinessLayer[]
     */
    public function & all($page = false, $pagesize = 20)
    {
        $return = [];
        $query = \Forge\Query::build()->select()->from($this->getTableName())->where('_deleted_at', null);
        if ($page !== false) $query->limit($page, $pagesize);
        $this->getDb()->setQuery($query);
        $this->getDb()->execute();
        if ($this->getDb()->hasRecords()) {
            while ($row = $this->getDb()->getRecord()) {
                $return[] = &$this->createObject($row);
            }
        }

        return $return;
    }

    /**
     * @param int $pagesize
     *
     * @return int
     */
    public function getPages($pagesize = 20)
    {
        return $this->_pages($pagesize);
    }

    /**
     * @param int $pagesize
     *
     * @return int
     */
    protected function _pages($pagesize = 20)
    {
        $this->getDb()
             ->setQuery(Query::build()->select(['num' => 'COUNT(id)'])->from($this->getTableName())->where('_deleted_at IS NULL'))
             ->execute();
        $row = &$this->getDb()->getRecord();

        return (int)(ceil($row['num'] / $pagesize));
    }

    /**
     * @param DataLayer|DataLayer[] $obj
     *
     * @return bool
     * @throws \Exception
     */
    public function persist(&$obj)
    {
        $success = true;
        if (is_array($obj)) {
            foreach ($obj as &$o) {
                $this->_persist($o);
            }
        } else {
            $this->_persist($obj);
        }

        return $success;
    }

    /**
     * @param DataLayer $obj
     *
     * @return bool
     * @throws \Exception
     */
    protected function _persist(&$obj)
    {
        //create a persistance object
        //This object will only form the needed sql statement
        $sql = new Persister($this->getDb()->getPrefix());
        if (get_class($obj) == 'Data\\' . $this->_getClassName() && $this->validate() && $obj->validate()) {
            $this->getDb()->setQuery($sql->getSql($obj));
            if ($this->getDb()->execute()) {
                if ($sql->getMethod() == 'Insert') {
                    $obj->id = $this->getDb()->getLastInsertID();
                    //alter state
                    $obj->state(DataLayer::STATE_LOADED);
                } else {
                    if ($this->getDb()->getAffectedRows() == 0)
                        throw new \Exception("Update failed: " . $sql->getSql($obj));
                    $obj->_record_version++;
                }
            }
        } else {
            var_dump($obj->getErrors());
            die();
        }
        return true;
    }

    /**
     * Delete a object
     *
     * @param DataLayer $obj
     *
     * @return bool $success
     */
    public function delete(DataLayer &$obj)
    {
        $success = true;
        if (is_array($obj)) {
            foreach ($obj as &$o) {
                if (!$success) continue; //skip others when not successful
                $this->_delete($o);
            }
        } else {
            $success = $this->_delete($obj);
        }

        return $success;
    }

    private function _delete(DataLayer &$obj)
    {
        //make sure the data object is passed to the correct database finder
        if (get_class($obj) == 'Data\\' . $this->_getClassName()) {
            return $this->getDb()
                        ->setQuery(
                            sprintf('UPDATE %s SET _deleted_at = %s WHERE id = "%s" AND _record_version <= "%s";',
                                $this->getTableName(),
                                time(),
                                $obj->id,
                                $obj->_record_version
                            )
                        )
                        ->execute();
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function validate()
    {
        return true;
    }

    public function __destroy()
    {
        foreach ($this as $key => $value)
            unset($this->$key);
        unset($this);
    }

}

?>