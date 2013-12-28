<?php
namespace Core;
/**
 * Description of commandhandler
 *
 * @author Darghon
 */
class CommandHandler {
	
	protected $type = null;
	protected $action = null;
	protected $params = array();
	protected $options = array();
	
	public function __construct($args) {
		//check if framework is loaded
		if(!class_exists('Forge')) self::bootForge();

		if(!isset($args[1])) trigger_error ('Invalid argument passed, needs to be in the form of type:action.'.PHP_EOL);
		@list($this->type,$this->action) = explode(':',$args[1]);

		if(count($args) > 2){
			foreach($args as $key => $arg){
				if($key <= 1) continue;
				if(substr($arg,0,2) == '--'){
					$this->options[] = $arg;
				}
				else{
					$this->params[] = $arg;
				}
			}
		}
	}
	
	public function run(){
		switch($this->type){
			case 'help':
				$this->showHelp();
				break;
			case 'build':
				Generator::getInstance()->build($this->action,$this->params,$this->options);
		}
	}

	private function showHelp(){
		print('********************'.PHP_EOL);
		print('*       HELP       *'.PHP_EOL);
		print('********************'.PHP_EOL);
		print(PHP_EOL);
		print('Available Commands: '.PHP_EOL);
		print('-------------------'.PHP_EOL);
		print(PHP_EOL);
		print('build:project'.PHP_EOL);
		print('  # This command creates the basic framework structure.'.PHP_EOL.
			  '  # Execute this command first when starting a new project'.PHP_EOL);
		print(PHP_EOL);
		print('build:application $app_name'.PHP_EOL);
		print('  # This command creates a new application.'.PHP_EOL.
			  '  # app_name is mandatory.'.PHP_EOL);
		print(PHP_EOL);
		$this->showMore();
		print(PHP_EOL);
		print('build:module $app_name $module_name [optional:$action_name]'.PHP_EOL);
		print('  # This command creates a new module in the selected application.'.PHP_EOL.
			  '  # $app_name must be an existing application, or run build:application first'.PHP_EOL.
			  '  # $module_name is mandatory, if it already exists, this will fail'.PHP_EOL.
			  '  # all parameters following the module name will be generated as new actions.'.PHP_EOL);
		print(PHP_EOL);
	}
	
	/**
	 * Show -press any key to continue- and wait for user enter
	 */
	private function showMore(){
		print(" - press any key to continue - ");
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
	}
	
	public function __destroy() {
		foreach ($this as $key => $var)
			unset($this->$key);
		unset($this);
	}
	
	/**
	 * Private static function to ensure that the framework has been booted.
	 */
	private static function bootForge(){
		$fw_dir = realpath(dirname(__FILE__)."/../core");

		//static require list
		require($fw_dir . "/forge.class.php");
		require($fw_dir . "/config.class.php");
        require($fw_dir . "/cache.class.php");
        require($fw_dir . "/yaml.class.php");

		//register default paths
		Config::registerPaths();
		Config::setMode(Config::CLI);
	}

}
?>