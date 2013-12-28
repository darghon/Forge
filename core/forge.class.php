<?php
namespace Core;

/**
 * Forge class will contain a single entry for every object that is created while the page is loading.
 * This will make sure that every object will only be created once, and will always be reused.
 * This class will never be used in the module development
 * This class uses the Singleton design pattern.
 */
class Forge{

	private static $memoryhandler = null;
	//private static $findercollection = array();
	//private static $objectcollection = array();
	private static $timercollection = array();
	private static $responsehandler = null;
	private static $databasehandler = null;
	private static $routehandler = null;
	private static $translationhandler = null;
	private static $requesthandler = null;
	private static $configurationhandler = null;
	private static $loghandler = null;
	private static $connections = array();
	private static $classBuffer = null;
	private static $variableHolder = array();
	private static $environment = null;
	private static $autoloaders = array('Forge::loadOnDemand');
	
	private static $version = null;
	
	private static $pageincludes = 0;

	/**
	 * Forge collects objects of every type.
	 * This function returns a requested finder from its finder collection.
	 * If the finder does not exist yet, it will initiate it, and add it to the collection
	 * A reference to the finder is returned to the requester
	 * @return Finder $class
	 */
	public static function & getFinder($class){
		return self::Memory()->retrieve($class);
	}

	/**
	 * This function checks if a given Business object is already within the collection.
	 * If so, it returns a reference to the object.
	 */
	public static function & getObject($class,$id){
		return self::Memory()->retrieve($class,$id);
	}
	
	/**
	 * Static function that returns an instance of the MemoryHandler Object
	 * @return MemoryHandler $memory
	 */
	public static function & Memory(){
		if(self::$memoryhandler === null) self::createMemory();
		return self::$memoryhandler;
	}

	/**
	 * Function that retrieves a timer from global collection
	 * @param String $tag
	 * @param boolean $start
	 * @return Timer
	 */
	public static function & getTimer($tag, $start = true){
		if(!isset(self::$timercollection[$tag]) || !array_key_exists($tag,self::$timercollection)){
			self::$timercollection[$tag] = new Timer($tag,$start);
		}
		return self::$timercollection[$tag];
	}

	/**
	 * Function that returns all initiated timers
	 * @return array
	 */
	public static function &getTimers(){
		return self::$timercollection;
	}

	/**
	 * This function returns the instance of the database handler object
	 * @return DatabaseHandler
	 */
	public static function & Database(){
		if(self::$databasehandler === null) self::createDatabase();
		return self::$databasehandler;
	}

	/**
	 * This function returns the instance of the application handler object
	 * @return ResponseHandler
	 */
	public static function & Response(){
		if(self::$responsehandler === null) trigger_error('No response stage handler has been specified.');
		return self::$responsehandler;
	}
	
	public static function setEnvironment($environment){
		self::$environment = $environment;
		//set global configuration
		Config::ApplyGlobalConfiguration();
	}
	
	public static function setVersion($version){
		self::$version = $version;
	}
	
	public static function getVersion(){ return self::$version; }

	/**
	 * This function returns the instance of the connection object
	 * @return \Connection
	 */
	public static function & Connection($index = null){
		if($index === null) $index = self::getDefaultConnection();
		if(!isset(self::$connections[$index]) || self::$connections[$index] === null) self::createConnection();
		if(isset(self::$connections[$index])){
			return self::$connections[$index];
		}
		else{
			throw new Exception("Connection '".$index."' not found.");
		}
	}

	/**
	 * This function returns the default connection name specified in the configuration
	 * @return String
	 */
	public static function getDefaultConnection(){
		if(self::$environment !== null) return self::$environment;
		$conf = Config::get("DBConfig");
		return $conf["default_connection"];
	}

	/**
	 * This function returns the instance the of route handler object
	 * @return \RouteHandler routehandler
	 */
	public static function & Route(){
		if(self::$routehandler === null) trigger_error('No route stage handler has been specified.');
		return self::$routehandler;
	}

	/**
	 * This function returns the instance the of translation handler object
	 * @return TranslationHandler translationhandler
	 */
	public static function & Translate(){
		if(self::$translationhandler === null) trigger_error('No translation handler has been specified.');
		return self::$translationhandler;
	}

	/**
	 * This function returns the instance the of request handler object
	 * @return \RequestHandler requesthandler
	 */
	public static function & Request(){
		if(self::$requesthandler === null) trigger_error('No request stage handler has been specified.');
		return self::$requesthandler;
	}

	/**
	 * This function returns the instance fo the log handler
	 * @return \LogHandler
	 */
	public static function & Log(){
		if(self::$loghandler === null) self::createLogHandler();
		return self::$loghandler;
	}
	
	public static function & Configuration(){
		if(self::$configurationhandler === null) trigger_error('No configuration stage handler has been specified.');
		return self::$configurationhandler;
	}

	/**
	 * private static function that creates the memoryhandler if it is not yet created
	 */
	private static function createMemory(){
		$settings = Config::get('settings');
		$mh = new MemoryHandler();
		if(isset($settings['memcached'])){
			$mh->setMode($settings['memcached']['enabled']);
			$mh->addServers($settings['memcached']['servers']);
		}
		else{
			$mh->setMode(MemoryHandler::MODE_REGISTRY); //registry is the default.
		}
		self::$memoryhandler =& $mh;
	}

	/**
	 * private static function that creates the database if it is not yet created
	 * (Will create the connection as well if that hasn't been created)
	 */
	private static function createDatabase(){
		$dbconf = Config::get('DBConfig');
		$db = new DatabaseHandler($dbconf["prefix"]);
		self::$databasehandler =& $db;
	}

	/**
	 * private static function that create the connection if it is not yet created
	 */
	private static function createConnection(){
		$config = Config::get('DBConfig');
		foreach($config["connections"] as $key => $entry){
			$conn = new Connection($entry);
			self::$connections[$key] = $conn;
		}
	}

	/**
	 * public static function that create the routehandler if it is not yet created
	 */
	public static function registerRouteHandler(&$rh){
		self::$routehandler =& $rh;
	}

	/**
	 * public static function that create the translationhandler if it is not yet created
	 */
	public static function registerTranslationHandler(&$th){
		self::$translationhandler =& $th;
	}

	/**
	 * public static function that create the requesthandler if it is not yet created
	 */
	public static function registerRequestHandler(&$rh){
		self::$requesthandler =& $rh;
	}

	/**
	 * public static function that create the responsehandler if it is not yet created
	 */
	public static function registerResponseHandler(&$rh){
		self::$responsehandler =& $rh;
	}

	/**
	 * private static function that create the loghandler if it is not yet created
	 */
	private static function createLogHandler(){
		$lh = new LogHandler();
		self::$loghandler =& $lh;
	}

	/**
	 * Set a configurationhandler object in forge
	 * @param ConfigurationHandler $ch
	 */
	public static function registerConfigurationHandler(&$ch){
		self::$configurationhandler =& $ch;
	}
	
	/**
	 * This function will add a given object, or array of objects to the target collection
	 */
	public static function & add($obj){
		return self::Memory()->register($obj);
	}

	/**
	 * this function forces a key update in objectcollection
	 */
	public static function update($object){
		return self::Memory()->update($object);
	}

	public static function & setVariableHolder($key,&$value){
		self::$variableHolder[$key] = &$value;
		return self::$variableHolder[$key];
	}
	
	public static function & getVariableHolder($key){
		$def = false;
		if(!key_exists($key,self::$variableHolder)){
			return $def;
		}
		return self::$variableHolder[$key];
	}

	/**
	 * Attempt to load a class location from cache, if this procedure fails, 
	 * the cache file is dropped and re-initialised. 
	 * @param $class
	 */
	private static function loadFromCache($class){
		if(self::$classBuffer === null){
			//load buffer
			self::$classBuffer = Cache::loadClassArray();
		}
		if(isset(self::$classBuffer[$class]) || array_key_exists($class, self::$classBuffer)){
			//check if file exists, if not, delete 'corrupt' buffer file
			if(file_exists(self::$classBuffer[$class])){
				include(self::$classBuffer[$class]);
				self::$pageincludes ++;
			}
			else{
				Cache::unlinkClassArray();
			}
		}
	}

	/**
	 * this function uses the paths config file to select where to include non existing functions
	 * or at least attempt to create it
	 */
	public static function loadOnDemand($class){
		//check cache
		self::loadFromCache($class);
		if(class_exists($class) || interface_exists($class)){
			return true;
		}
	    //retrieve namespaces configuration
		$namespaces = Config::getAutoloaderNamespaces();

        $classlist = explode('\\',$class);
        $classname = array_pop($classlist);
        $namespace = implode('\\', $classlist);

        if(substr($namespace,0,1) != '\\') $namespace = '\\'.$namespace;
        if(substr($namespace,-1) != '\\') $namespace .= '\\';

        if(!isset($namespaces[$namespace])) throw new \Exception('Requested class is part of an unknown namespace. Please complete namespaces.yml configuration to include: '.$namespace);

        $paths = $namespaces[$namespace];

        $location = null;
		foreach($paths as $path){
			//check identical filename
			if(file_exists(Config::path("root")."/".$path."/".$classname.".class.php")){
				$location = Config::path("root")."/".$path."/".$classname.".class.php";
				include(Config::path("root")."/".$path."/".$classname.".class.php");
				self::$pageincludes ++;
				break;
			}
			//check lowercase filename
			if(file_exists(Config::path("root")."/".$path."/".strtolower($classname).".class.php")){
				$location = Config::path("root")."/".$path."/".strtolower($classname).".class.php";
				include(Config::path("root")."/".$path."/".strtolower($classname).".class.php");
				self::$pageincludes ++;
				break;
			}
		}
		if($location !== null){
			Cache::addClassLocation($class, $location);
			self::$classBuffer[$class] = $location;
			return true;
		}
        else{
            throw new \Exception('Can not locate class '.$class);
        }
		
	}

	public static function registerAutoLoader($loader = null){
		if($loader !== null) self::$autoloaders[] = $loader;
		foreach(self::$autoloaders as $loader)
			spl_autoload_register($loader);
	}
	
	public static function unregisterAutoLoader($loader){
		if(in_array($loader, self::$autoloaders)){
			self::$autoloaders = array_diff(self::$autoloaders,array($loader));
			spl_autoload_unregister($loader); 
		}
	}
	
	public static function getPageIncludes(){
		return self::$pageincludes;
	}
	
}
spl_autoload_register("Core\\Forge::loadOnDemand");
