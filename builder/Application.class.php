<?php
namespace Forge\Builder;

/**
 * Generator that builds the folder structure of a new application.
 * When this object is called to generate, it will check if the 
 * root folder of the application already exists, if so, the application is 
 * considered "generated". if not, it will attempt to create all 
 * neccesary folders and default files
 *
 * @author Gerry Van Bael
 */
class Application extends \Forge\baseGenerator {

	protected $app_name = null;
	/**
	 * Public constructor that receives array of parameters, this class requires 1 argument, 
	 * which will be used as the application name.
	 * @param array empty
	 */
	public function __construct($args = array()){
		if(!isset($args[0]) || $args[0] == ''){
			throw new \Exception('No application name was passed.');
		}
		$this->app_name = \Forge\Tools::slugify($args[0]);
	}

	/**
	 * Public generate action. This method performs all actions required to build the wanted files
	 * @return boolean $result; 
	 */
	public function generate(){
		if(!file_exists(\Forge\Config::path("app")."/".$this->app_name)){
			//folder does not exists, so ok to proceed
			$this->generateFolderStructure();
			$this->generateDefaultFiles();
			return true;
		}
		else{
			print('Application has already been build.'.PHP_EOL);
			return false;
		}
	}
	
	public function generateFolderStructure(){
		print('Creating folder structure'); flush();
		print((mkdir(\Forge\Config::path("app").'/'.$this->app_name, 0777) ? '.' : 'x')); flush();
		print((mkdir(\Forge\Config::path("app").'/'.$this->app_name.'/config', 0777) ? '.' : 'x')); flush();
		print((mkdir(\Forge\Config::path("app").'/'.$this->app_name.'/modules', 0777) ? '.' : 'x')); flush();
		print((mkdir(\Forge\Config::path("app").'/'.$this->app_name.'/templates', 0777) ? '.' : 'x')); flush();
		print('DONE'.PHP_EOL); flush();
	}
	
	public function generateDefaultFiles(){
		print('Creating default files'); flush();
		$templates = scandir(\Forge\Config::path('forge').'/templates');
		foreach($templates as $template){
			$file = explode('_',$template);
			if($file[0] == 'application'){
				array_splice($file,0,1); //drop the "application" part of the filename
				
				$new_file = implode('/',$file);
				$new_file = \Forge\Config::path('app').'/'.$this->app_name.'/'.substr($new_file,0,strlen($new_file)-9);
				$contents = file_get_contents(\Forge\Config::path('forge').'/templates/'.$template);
				chmod(\Forge\Config::path('forge').'/templates/'.$template, 0777);
				file_put_contents($this->replaceTokens($new_file),$this->replaceTokens($contents));
				print('.'); flush();
			}
		}
		print('DONE'.PHP_EOL);
	}

	public function __destroy(){
		unset($this);
	}
}
