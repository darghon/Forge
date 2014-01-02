<?php
namespace Core;

abstract class BusinessLayer {

	/**
	 * Dataobject that is embedded within the business object
	 * @var DataLayer
	 */
	protected $data = null;

	/**
	 * New objects require a new instance of 
	 */
	public function __construct($data = null) {
		$class = '\\Data\\'.$this->getClassName();
		if (get_class($data) == $class) {
			$this->data = $data;
		} else {
			$this->data = new $class();
		}
	}

	public function _set($var, $value) {
		$this->$var = $value;
	}

	/**
	 * This function should be used as little as possible
	 */
	public function fillDataInto(&$object) {
		if (method_exists(get_class($object), '_set')) {
			$object->_set('data', $this->data);
			return true;
		}
		return false;
	}

	public function fromArray($arr) {
		foreach ($arr as $field => $value) {
			$fun = "set" . Tools::strtocamelcase($field, true);
			if ($fun == "setId")
				$fun = "setID";
			if (!method_exists($this, $fun))
				continue;
			if (strpos($fun, "ID") > -1) {
				$this->$fun((int) $value);
			} else {
				$this->$fun($value);
			}
		}
	}

	public function toArray() {
		$result = array();
		$fields = $this->data->getFields();
		foreach ($fields as $field => $changed) {
			$result[$field] = $this->data->$field;
		}
		return $result;
	}

	public function fromXML(XMLNode &$xml) {
		foreach ($xml->children as &$child) {
			$field = $child->getName();
			if ($child->hasChildren()) {
				$this->data->$field = $child->firstChild()->getText();
			}
		}
	}

	public function toXML($rootName = null) {
		$rootName = $rootName == null ? get_class($this) : $rootName; //default will be the name of the object, without the D for data
		$root = new XMLNode($rootName);
		$fields = $this->data->getFields();
		foreach ($fields as $field => $value) {
			$child = new XMLNode($field);
			if ($this->data->$field != '' && $this->data->$field != null) {
				$child->appendChild(new XMLTextNode($this->data->$field));
			}
			$root->appendChild($child);
		}
		return $root;
	}

	public function state($state) {
		$this->data->state($state);
	}

	/**
	 * Public persist method.
	 * This method checks hooks prePersist and postPersist to do additional functionality
	 * @return boolean 
	 */
	public function persist() {
		if (method_exists($this, 'prePersist')) $this->prePersist();
		$old_id = $this->getID();
        //Push this and related objects to database
		if ($this->validate()) {
			if (Database::Persist($this->data)) {
				if ($old_id == 0) Forge::add($this);
				elseif ($old_id != $this->getID()) Forge::update($this, $old_id);
				else Forge::update($this);

				if (method_exists($this, 'postPersist')) $this->postPersist();
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Persist as new allowed the user to save the current object as a new instance of itself.
	 * This is handy for duplicating an object into a new one, without losing the old one.
	 * @return boolean 
	 */
	public function persistAsNew(){
		if (method_exists($this, 'prePersist')) $this->prePersist();

        $this->data->state(DataLayer::STATE_NEW);
		$this->data->ID = null;

		//Push this and related objects to database
		if ($this->validate()) {
			if (Database::Persist($this->data)) {
				Forge::add($this);
				
				if (method_exists($this, 'postPersist')) $this->postPersist();
				return true;
			}
		}
		return false;
	}

	/**
	 * Public function that removes the current object.
	 * This function will never truely delete the object, just toggle the internal deleted_at
	 * @return Boolean 
	 */
	public function delete() {
		if (method_exists($this, 'preDelete')) $this->preDelete();
		if (Database::Delete($this->data)) {
			Forge::update($this);
			if (method_exists($this, 'postDelete')) $this->postDelete();
			return true;
		}
		else
			return false;
	}

	public function getSql(Persister &$persister) {
		return $persister->getSql($this->data);
	}

	public function getSmallSql(Persister &$persister) {
		return $persister->getSmallSql($this->data);
	}

	public function validate() {
		return true;
	}

    /**
     * Retrieve the classname of this class. This method is needed to allow extending of business classes, without breaking the natural build of the object
     * @return String $className
     */
    protected function getClassName(){
        return array_pop(explode('\\',get_class($this)));
    }

	/**
	 * Static function that creates a "Find" static function to each business object,
     * which in turn is basicly a shortkey to get The Findertype, or when an ID is passed, to get the object by that ID
	 * @return BusinessLayer|Finder
	 */
	public static function & Find($id = null) {
        $caller = function_exists('get_called_class') ? get_called_class() : Tools::getCaller();
        $caller = 'Finder\\'.array_pop(explode('\\',$caller));
        if ($id !== null) {
            $return = &Database::Find($caller)->byID($id);
        } else {
            $return = &Database::Find($caller);
        }
        return $return;
    }

    public static function is_a($class_name) {
        return (__CLASS__ == $class_name) ? true : false;
    }

}

?>