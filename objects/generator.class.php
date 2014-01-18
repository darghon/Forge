<?php
namespace Core;
/**
 * Class Generator
 * @package Core
 */
class Generator extends Singleton {
	
	/**
	 * @var IGenerator[] $_builders
	 */
	protected $_builders = array();

    /**
     * @var bool $_initiating
     */
    protected $_init = true;

    /**
     * @param string $type
     * @param array $arguments
     * @throws \InvalidArgumentException
     */
    public function build($type, $args = array()){
        //retrieve all available builders if not done already
        if($this->_init) $this->initBuilders();

		if(isset($this->_builders[$type]) || array_key_exists($type,$this->_builders)){
			$builder = new $this->_builders[$type]($args);
			if($builder instanceOf IGenerator){ //make sure builder has the generator interface
				$builder->generate();
			}
		}
		else{
			throw new \InvalidArgumentException('Requested builder '.$type.' was not found. Request can not be executed.'.PHP_EOL);
		}
	}

    /**
     * Retrieve a list of available builders
     * @return array
     */
    public function getAvailableBuilders(){
        //retrieve all available builders if not done already
        if($this->_init) $this->initBuilders();

        return array_keys($this->_builders);
    }

    /**
     * @param string $type
     * @param string $class
     * @param bool $overwrite
     * @return bool $registered
     */
    public function registerBuilder($type, $class, $overwrite = false){
		if((isset($this->_builders[$type]) || array_key_exists($type,$this->_builders)) && $overwrite == false){
			echo "A builder of this type has already been registered.";
			return false;
		}
		if(!is_callable(array($class,'isGenerator')) === true || !$class::isGenerator() === true){
			echo "The class $class is not an implementation of the IGenerator interface.".PHP_EOL;
			return false;
		}
        $this->_builders[$type] = $class;
		return true;
	}

    /**
     * Initiate all builders found in builder directory
     */
    public function initBuilders(){
        $builderPath = Config::path('forge').DIRECTORY_SEPARATOR.'builder';
        $path = opendir($builderPath);
        while (false !== ($filename = readdir($path))){
            if(substr($filename,0,1) != '.'){
                $this->registerBuilder(strtolower(substr($filename,0,-10)),'\\Core\\Builder\\'.substr($filename, 0, -10), true);
            }
        }
        $this->_init = false;
    }

    /**
     * Implement getInstance for correct code completion
     * @return Generator
     */
    public static function getInstance(){
        return parent::getInstance();
    }

}

?>