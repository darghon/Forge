<?php
namespace Forge;
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
        'task' => '_runTask',
        'build' => '_build'
    );
	
	public function __construct($args) {
		//check if framework is loaded
		if(!class_exists('Forge')) self::bootForge();
        if(count($args) > 1){
            array_shift($args); //drop first element
            $command = array_shift($args);
            $this->_runCommand($command, $args);
            exit;
        }
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
        $builder = new \Forge\Generator();
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

    protected function _runTask($arguments){
        //load task
        unset($environment);
        $task = array_shift($arguments);
        if($task !== null){
            $path = sprintf('%s/%s.task.php',Config::path("lib"), $task );
            if(file_exists($path)){
                include $path;
            }
            else{
                echo "Requested task does not exist.".PHP_EOL;
            }
        }
        print 'DONE'.PHP_EOL;
        print PHP_EOL;
    }

    protected function _showCommands(){
        print('Available commands:'.PHP_EOL);
        print(PHP_EOL);
        print('  ? (show this list)'.PHP_EOL);
        print('  build <type> <arguments> (Run a specific builder, type build ? for a list of available builders)'.PHP_EOL);
        print('  task <scriptname> <arguments> (Run a specific task, tasks are defined in the shared/lib folder and have a ".task.php" extention)'.PHP_EOL);
        print('  exit (quit the prompt)'.PHP_EOL);
        print('  quit (same as exit)'.PHP_EOL);
        print(PHP_EOL);
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