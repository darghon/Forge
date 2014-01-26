<?php
namespace Forge\Builder;

/**
 * Generator that builds the folder structure of the framework.
 * When this object is called to generate, it will check if the 
 * project configuration already exists, if so, the project is 
 * considered "generated". if not, it will attempt to create all 
 * neccesary folders and default configuration files
 *
 * @author Darghon
 */
class Project extends \Forge\baseGenerator{

	/**
	 * Public constructor that receives an in this case empty parameter array.
	 * @param array empty
	 */
	public function __construct($args = array()){}

	/**
	 * Public generate action. This method performs all actions required to build the wanted files
	 * @return boolean $result; 
	 */
	public function generate(){
		if(!file_exists(\Forge\Config::path("config")."/projectConfigurator.class.php")){
			//folder does not exists, so ok to proceed
			$this->generateFolderStructure();
			$this->generateDefaultFiles();
			return true;
		}
		else{
			print('Project has already been build.'.PHP_EOL);
			return false;
		}
	}
	
	public function generateFolderStructure(){
        /** Todo: Refactor all paths to create a new and correct structure */
		print('Creating folder structure'); flush();
        $this->_createDirectory(array(
            \Forge\Config::path("app"),
            \Forge\Config::path("config").'/database',
            \Forge\Config::path("lib"),
            \Forge\Config::path("objects").'/business/base',
            \Forge\Config::path("objects").'/data/base',
            \Forge\Config::path("objects").'/finders/base',
            \Forge\Config::path("public").'/css',
            \Forge\Config::path("public").'/js',
            \Forge\Config::path("public").'/images',
            \Forge\Config::path("public").'/plugins',
            \Forge\Config::path("public").'/uploads',
        ), '.', 'x');
		print('DONE'.PHP_EOL); flush();
	}
	
	public function generateDefaultFiles(){
		print('Creating default files'); flush();
		$templates = scandir(\Forge\Config::path('forge').'/templates');
		foreach($templates as $template){
			$file = explode('_',$template);
			if($file[0] == 'config' || $file[0] == 'public' ||  $file[0] == 'lib' || substr($file[0],0,9) == '.htaccess'){
				$root = count($file) == 1 ? 'root' : implode('',array_splice($file,0,1));
				
				$new_file = implode('/',$file);
				copy(\Forge\Config::path('forge').'/templates/'.$template,\Forge\Config::path($root).'/'.substr($new_file,0,strlen($new_file)-9));
				print('.'); flush();
			}
		}
		print('DONE'.PHP_EOL);
	}

	public function __destroy(){
		unset($this);
	}
}
