<?php
namespace Forge;
/**
 * Description of stagehandler
 *
 * @author Darghon
 */
class StageHandler {

	protected static $current_stage = 0; //Boot
	
	protected $stages = array();
	
	protected $defaults = array(
		'request' => '\Forge\RequestHandler',
		'routing' => '\Forge\RouteHandler',
		'configuration' => '\Forge\ConfigurationHandler',
		'security' => '\Forge\SecurityHandler',
		'response' => '\Forge\ResponseHandler'
	);
	
	public function __construct($config) {
		foreach($config as $key => $stage){
			$s = $stage == '~' ? (isset($this->defaults[$key]) || array_key_exists($key,$this->defaults) ? new $this->defaults[$key] : null) : new $stage;
			if($s instanceOf IStage) $this->stages[$key] = $s;
			else trigger_error(T::__('Attempting to register an invalid stage. Make sure that the stage exists and implements IStage interface','core'),E_ERROR);
			
			if($key == 'request') Forge::registerRequestHandler($this->stages[$key]); //register request handler
			if($key == 'routing') Forge::registerRouteHandler($this->stages[$key]); //register routing handler
			if($key == 'configuration') Forge::registerConfigurationHandler($this->stages[$key]); //register configuration handler
			if($key == 'response') Forge::registerResponseHandler($this->stages[$key]); //register response handler
			unset($s);
		}
	}
	
	public function deploy(){
		foreach($this->stages as $key => &$stage){
			self::$current_stage++;
			Debug::Notice('Stage', 'Initializing '.$key);
			$stage->initialize();
			Debug::Notice('Stage', 'Deploying '.$key);
			$stage->deploy();
		}
        //register end of request
        Forge::registerShutdown();
        return true;
	}
	
	public static function getCurrentStage(){
		return self::$current_stage;
	}
	
	public function __destroy() {
		foreach($this as $key => $var)
			unset($this->$key);
		unset($this);
	}

}

?>
