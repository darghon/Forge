<?php
namespace Forge;

/**
 * ObjectFactory is an implementation of the Factory Design Pattern.
 * This static class is used to centralise the construction of each object that is retrieved by a finder.
 *
 * @author Gerry
 */
class ObjectFactory {

	/**
	 * Static called function that creates the object requested with any data 
	 * arguments that have been passed
	 * @param IObject $businesslayer_object
	 * @param Array $arguments
	 * @return BusinessLayer $object 
	 */
	public static function & build($type, $args = array()) {
		$default = false;
		$business = new $type();
		if(isset($args['ID'])){
			$ob = &Forge::getObject($type, $args['ID']);
			//if found && still valid
			if($ob instanceOf $type && (!isset($args['_recordVersion']) || (isset($args['_recordVersion']) && $args['_recordVersion'] <= $ob->getRecordVersion()))){
				return $ob;
			}
		}
		if (is_a($business,'\\Forge\\BusinessLayer')) {
			$dtype = '\\Data\\'.$type;
			$data = new $dtype();
			if(is_a($data,'\\Forge\\DataLayer')){
				$data->init(true);
				foreach($args as $key => $value) if($data->hasProperty($key)) $data->$key = !is_null($value) ? stripslashes($value) : null;
				$data->init(false);
				$data->state(DataLayer::STATE_LOADED);
			}
			$business->_set("data",$data);
			return Forge::add($business);
		}
		return $default;
	}

}

?>
