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

    protected $_commandMapping = array(
        '?' => '_showCommands',
        'exit' => true,
        'quit' => true,
        'build' => '_build'
    );
	
	public function __construct($args) {
		//check if framework is loaded
		if(!class_exists('Forge')) self::bootForge();

	}
	
	public function run(){

        print(PHP_EOL);
        print("/**********************************************************/".PHP_EOL);
        print("/*                                                        */".PHP_EOL);
        print("/*          FORGE Command line interface - v1.0           */".PHP_EOL);
        print("/*                                                        */".PHP_EOL);
        print("/**********************************************************/".PHP_EOL);
        print(PHP_EOL);
        print("(Type ? for a list of available actions.)".PHP_EOL);
        $exit = false;
        while(!$exit){
            print("> ");
            $handle = fopen ("php://stdin","r");
            $entry = explode(' ',trim(fgets($handle)));
            $command = array_shift($entry);
            $exit = $this->_runCommand($command, $entry);
        }
	}

    /**
     * @param $command
     * @return bool $exit
     */
    protected function _runCommand($command, $arguments) {

        switch($command){
            case '?':
                $this->_showCommands();
                break;
            case 'exit':
            case 'quit':
                return true;
                break;
            default:
                if(array_key_exists($command, $this->_commandMapping)) {
                    $this->{$this->_commandMapping[$command]}($arguments);
                }
                else{
                    print('Invalid command, type ? for a list of available actions'.PHP_EOL);
                }
                break;
        }
        return false;

    }

    protected function _build($arguments) {
        $builder = new \Core\Generator();
        $type = array_shift($arguments);
        if($type == '?') {
            $list = $builder->getAvailableBuilders();
            foreach($list as $entry){
                printf('  build %s <arguments>'.PHP_EOL,$entry);
            }
        }
        else{
            $builder->build($type,$arguments);
        }
    }

    protected function _showCommands(){
        print('Available commands:'.PHP_EOL);
        print(PHP_EOL);
        print('  ? (show this list)'.PHP_EOL);
        print('  build <type> <arguments> (Run a specific builder, type build ? for a list of available builders)'.PHP_EOL);
        print('  exit (quit the prompt)'.PHP_EOL);
        print('  quit (same as exit)'.PHP_EOL);
        print(PHP_EOL);
    }

//	private function showHelp(){
//		print('********************'.PHP_EOL);
//		print('*       HELP       *'.PHP_EOL);
//		print('********************'.PHP_EOL);
//		print(PHP_EOL);
//		print('Available Commands: '.PHP_EOL);
//		print('-------------------'.PHP_EOL);
//		print(PHP_EOL);
//		print('build:project'.PHP_EOL);
//		print('  # This command creates the basic framework structure.'.PHP_EOL.
//			  '  # Execute this command first when starting a new project'.PHP_EOL);
//		print(PHP_EOL);
//		print('build:application $app_name'.PHP_EOL);
//		print('  # This command creates a new application.'.PHP_EOL.
//			  '  # app_name is mandatory.'.PHP_EOL);
//		print(PHP_EOL);
//		$this->showMore();
//		print(PHP_EOL);
//		print('build:module $app_name $module_name [optional:$action_name]'.PHP_EOL);
//		print('  # This command creates a new module in the selected application.'.PHP_EOL.
//			  '  # $app_name must be an existing application, or run build:application first'.PHP_EOL.
//			  '  # $module_name is mandatory, if it already exists, this will fail'.PHP_EOL.
//			  '  # all parameters following the module name will be generated as new actions.'.PHP_EOL);
//		print(PHP_EOL);
//	}
	
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