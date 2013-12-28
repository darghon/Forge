<?php
namespace Core;

/**
 * Session is a Static called class which provides structured access to the session object
 * This class is build with a factory design pattern, to allow additional session handlers to be registered and used.
 * Changing the session handler will not alter the usage of the Session object
 */
class Session {

	/**
	 * Collection of predefined additional session handlers.
	 * By default, none of these is used, and Forge will use the basic configuration of PHP.ini
	 * @var Array 
	 */
	protected static $handlers = array(
		'database' => 'SessionDatabaseHandler'
	);

	/**
	 * An object containing the ISessionHandler interface.
	 * If this object is null, the system will default to PHP.ini for session handling
	 * @var ISessionHandler 
	 */
	protected static $_session = null;

	/**
	 * Static method to start session usage
	 * If a handler is passed, that implements the ISessionHandler interface, 
	 * it will be registered as an alternative session handler
	 * The passed handler can be a reference to a registered handler, or the class name
	 * @param String $handler 
	 * @return Boolean $success
	 */
	public static function start($handler = null) {
		if ($handler !== null) {
			if (isset(self::$handlers[$handler]) || array_key_exists($handler, self::$handlers))
				self::$_session = new self::$handlers[$handler];
			else {
				$sh = new $handler;
				if($sh instanceOf ISessionHandler) self::$_session = &$sh;
				else return false;
			}
		}
		if (self::$_session !== null) {
			session_set_save_handler(
					array(&self::$_session, 'open'), //Open session
					array(&self::$_session, 'close'), //Close session
					array(&self::$_session, 'get'), //Read session
					array(&self::$_session, 'set'), //Write session
					array(&self::$_session, 'destroy'), //Destroy session
					array(&self::$_session, 'clean')); //Cleanup by Garbage Collector
		}

		//session_start(); //start session handler
		return true;
	}
	
	/**
	 * register a custom created session handler to handle session events.
	 * The class passed for session handling must implement the ISessionHandler interface.
	 * If not, false is returned.
	 * @param String $name identifier
	 * @param String $handler class_name
	 * @return Boolean $success
	 */
	public static function registerHandler($name, $handler){
		if(isset(self::$handlers[$name]) || array_key_exists($name,self::$handlers)){
			trigger_error('There is already a session handler registered by that name.');
			return false;
		}
		else{
			$sh = new $handler;
			if(!$sh instanceOf ISessionHandler){
				trigger_error('Only classes implementing the ISessionHandler interface can be registered as SessionHandlers');
				return false;
			}
			else{
				self::$handlers[$name] = $handler;
				return true;
			}
		}
	}

	/**
	 * Retrieve a variable that has been saved in the session
	 * This can be namespaced by dividing the name by /
	 * If the session variable does not exist, return default value
	 * @param String $var_name
	 * @param Object $default Null
	 * @return Object
	 */
	public static function get($var_name, $default = null) {
		$name = explode("/", strtolower($var_name));
		$curr = &$_SESSION;
		foreach ($name as $entry) {
			if (isset($curr[$entry])) {
				$curr = &$curr[$entry];
			} else {
				return $default;
			}
		}
		return $curr;
	}

	/**
	 * Set a variable in the session
	 * This can be namespaced by dividing the name by /
	 * @param String $var_name
	 * @param Object $value
	 */
	public static function set($var_name, $value = null) {
		$name = explode("/", strtolower($var_name));
		$curr = &$_SESSION;
		foreach ($name as $entry) {
			if(!isset($curr[$entry])) $curr[$entry] = false;
			$curr = &$curr[$entry];
		}
		$curr = $value;
	}

	/**
	 * Add a flash message to the session, this will be used to display events once.
	 * @param String $tag
	 * @param String $message
	 */
	public static function addFlash($tag = 'notice', $message = '') {
		if (!isset($_SESSION['flash'][$tag]))
			$_SESSION['flash'][$tag] = array();
		$_SESSION['flash'][$tag][] = $message;
	}

	/**
	 * Retrieve a flash by tag, this could be an array, in which case it will be imploded with the split variable as glue
	 * @param String $tag
	 * @param String $default default value if flash message isn't found.
	 * @param String $split optional glue to paste flash messages together
	 * @return String Flash Messages
	 */
	public static function getFlash($tag, $default = '', $split = '<br />') {
		if (isset($_SESSION['flash'][$tag])) {
			$return = implode($split, $_SESSION['flash'][$tag]);
			unset($_SESSION['flash'][$tag]);
			return $return;
		} else {
			return $default;
		}
	}

	/**
	 * Check if Session contains any flashes, or the passed flash type
	 * @param String $tag
	 * @return boolean
	 */
	public static function hasFlash($tag = null) {
		if ($tag === null) {
			//check all flash messages
			foreach ($_SESSION['flash'] as &$collection) {
				if (count($collection) > 0) {
					return true;
				}
			}
		} else {
			if (isset($_SESSION['flash'][$tag])) {
				return count($_SESSION['flash'][$tag]) > 0 ? true : false;
			}
		}
		return false;
	}

	/**
	 * Retrieve a serialized version of the user session object;
	 * @return String 
	 */
	public static function serialize() {
		return serialize(isset($_SESSION) ? $_SESSION : array());
	}

	/**
	 * Clear all set variables from the $_SESSION object.
	 */
	public static function clear() {
		$_SESSION = array();
	}
	
	/**
	 * Set or Get a session language
	 * If a parameter is passed, the session language is set to that parameter
	 * This function will always return the language code of the setting
	 * or null if not language has been set
	 * @param String $iso2 
	 */
	public static function language($iso2 = null){
		self::set('language',$iso2);
		return self::get('language',null);
	}
	
	/**
	 *
	 * @return SecureUser 
	 */
	public static function & getActiveUser(){
		$default = null;
		
		if(isset($_SESSION['active_user'])) return $_SESSION['active_user'];
		return $default;
	}
	
	public static function setActiveUser($user){
		$_SESSION['active_user'] = &$user;
	}
}