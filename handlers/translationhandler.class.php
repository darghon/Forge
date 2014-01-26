<?php
namespace Forge;
/**
 * Creation of this object requires 
 */
class TranslationHandler{
	
	protected $default_language = 'en_GB';
	protected $available_languages = array();
	protected $date_format = array();
	protected $current_language = 'en_GB';	
	protected $location = null;
	protected $enabled = false;
	protected $active_translation = false;
	protected $buffer = array();
	/**
	 * TranslationHandler is a class that contains all multilingual defined parameters.
	 * Check the config/settings.yml for information on how to configure this class
	 * @param type $config
	 */
	public function __construct($config = null){
		if($config !== null){
			$this->location = isset($config['translations']) ? Config::path('root').DIRECTORY_SEPARATOR.$config['translations'] : '';
			$this->enabled = isset($config['enabled']) ? $config['enabled'] : false;
			$this->active_translation = isset($config['active_translations']) ? $config['active_translations'] : false;
			$this->available_languages = isset($config['available']) ? $config['available'] : array('en_GB');
			$this->default_language = isset($config['default']) ? $config['default'] : 'en_GB';
			$this->date_format = isset($config['date_format']) ? $config['date_format'] : array('default' => 'Y-m-d');
			
			if(!in_array($this->default_language, $this->available_languages)) trigger_error ('Default language is found a available language.');
			
			$this->_detectCurrentLanguage();
		}
	}
	
	protected function _detectCurrentLanguage(){
		//check url
		if(isset($_GET['language']) && in_array($_GET['language'], $this->available_languages)) $this->current_language = $_GET['language'];
		//check session
		if(Session::get('language',null) !== null && in_array(Session::get('language',null), $this->available_languages)) $this->current_language = Session::get('language');
		//check cookie TODO: to be implemented
		//set default
		 $this->current_language = $this->default_language;
	}
	
	/**
	 * Translate a specific string to the current language
	 * @param String $category
	 * @param String $text
	 * @param Array $params
	 * @return String
	 */
	public function translate($category,$text,$params = array()){
		return $this->processTranslation($this->enabled ? $this->retrieveTranslation($category, $text) : $text, $params);
	}
	
	public function formatDate($date){
		return date(isset($this->date_format[$this->current_language]) ? $this->date_format[$this->current_language] : $this->date_format['default'],strtotime($date));
	}
	
	public function normalizeDate($date){
		$nDate = preg_split('/[^a-zA-Z0-9]+/', $date); //split on all non letter chars
		$nFormat = preg_split('/[^a-zA-Z]+/', isset($this->date_format[$this->current_language]) ? $this->date_format[$this->current_language] : $this->date_format['default']);
		$year = $month = $day = 0;
		foreach($nFormat as $key => $type){
			switch($type){
				case 'Y':
					$year = $nDate[$key];
					break;
				case 'm':
					$month = $nDate[$key];
					break;
				case 'd':
					$day = $nDate[$key];
					break;
			}
		}
		return date('Y-m-d', strtotime("$year-$month-$day"));
	}
	
	protected function retrieveTranslation($cat,$text){
		if(!isset($this->buffer[$cat])){
			//retrieve translation file
			$this->_loadTranslation($cat);
		}
		//return the translation text, or the text itself
		if(isset($this->buffer[$cat][$text]) && $this->buffer[$cat][$text] != '') return isset($this->buffer[$cat][$text]);
		else{
			//add empty entry to translation file
			$this->buffer[$cat][$text] = '';
			return $text;
		}
	}
	
	protected function processTranslation($text,$params = array()){
		$inline_params = array();
		preg_match_all("/{([^\}]*)}/i", $text, $inline_params);
		$replacements = array();
		if (!empty($inline_params)) {
			foreach ($inline_params[1] as $index => $param) {
				if (isset($params[$param])) { //basic parameter to replace
					$replacements[$inline_params[0][$index]] = $params[$param]; //add parameter to replacement array
				} elseif (strpos($param, '|') > -1) { //possible plural to apply
					$expl = explode('|', $param);
					$a = (!isset($params[0])) ? 0 : (int) ($params[0]);
					$replacements[$inline_params[0][$index]] = ($a != 1) ? $expl[1] : $expl[0];
				} elseif ($param == 'n') {//special param for numbers
					$replacements[$inline_params[0][$index]] = (!isset($params[0])) ? 0 : (int) ($params[0]);
				} else {
					Debug::Notice('Translation', 'Parameter "' . $param . '" in the string "' . $text . '" could not be translated.');
				}
			}
		}
		return str_replace(array_keys($replacements), array_values($replacements), $text);
	}

    public function getActiveLanguage(){
        return $this->current_language;
    }
	
	protected function _loadTranslation($cat, $lang = null){
		if($lang === null) $lang = $this->current_language;
		$translations = array();
		if(file_exists($this->location.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$cat.'.i18n.php')){
			$translations = include($this->location.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$cat.'.i18n.php');
		}
		$this->buffer[$cat] = &$translations;
	}
	
	public function __destruct() {
		//if active translation generation is enabled, make sure all translation files are created first
		if($this->active_translation === true){
			$lang = $this->current_language;
			if(!file_exists($this->location.DIRECTORY_SEPARATOR.$lang)) mkdir($this->location.DIRECTORY_SEPARATOR.$lang,0777, true); //make folder is not exists
			//recreate the file
			foreach($this->buffer as $category => $translations){
				$values = array();
				foreach($translations as $a => $b) $values[] = sprintf("\t".'"%s"=>"%s"',$a,$b);
				$file = fopen($this->location.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$category.'.i18n.php','w');
				$content = file_get_contents(Config::path('forge').DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'{category}.i18n.php.skel');
				fwrite($file,sprintf($content,implode(','.PHP_EOL,$values)));
				fclose($file);
				chmod($this->location.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.$category.'.i18n.php',777);
			}
		}
		foreach($this as $key => $value) unset($this->$key);
		unset($this);
	}
}
/*
 *   enabled: true
  # Default language that is used if no previous language has been specified. This value needs to be present in the available list.
  default: en_GB
  # List of available languages, the system will create a translation file for each of these language codes.
  # Translation files will be grouped in folders identical to the chosen language code (Ex. "en", "en_GB", "english", ...)
  available:
    -en_GB
    -nl_NL
    -fr_FR
    -de_DE
  # folder of the translation files. This folder is contained on application base (application/<app_name>/i18n
  translations: i18n
  # It is possible to define a date format according to the language that is currently loaded.
  # If no definition has been added, default will be used
  date_format:
    default: yyyy-mm-dd
    nl_NL: dd/mm/yyyy
 */


class OldTranslationHandler {
  private $language = null;
  private $buffer = array();
  private $path = null;
  private $language_config = null;

  public function __construct() {
  //load language config
    $config = &Config::get("settings");
    if(isset($config["multilingual"]) && $config["multilingual"]["enabled"] == true) {
      $this->language_config = $config["multilingual"];
    }
    else {
      trigger_error("multilingual settings are non existing or disabled.");
    }
    $this->setLanguage();
    $this->path = Config::path("root").'/application/'.Route::curr_app().'/i18n';
  }

  public function setLanguage($lang = null) {
    if($lang == null) {
      if(Session::get("language",null)!=null) {
        $this->language = Session::get("language");
      }
      else {
        $this->language = $this->language_config["default"];
      }
    }
    else {
      $this->language = $lang;
    }
    //set session
    Session::set("language",$this->language);
  }

  public function setPath($path){
    $this->path = $path;
  }

  public function getAvailableLanguages(){
    return (isset($this->language_config))?$this->language_config["available"]:array();
  }

  public function get($group, $tag) {
  //load translation file
    if(!isset($this->buffer[$group]) && file_exists($this->path."/".$group.".xml")) {
      //check in cache
      if(null === ($buff = Cache::load('translationcache_translate_'.$group.'.php',null))){
        $xml = new XMLDocument($this->path."/".$group.".xml");
        foreach($xml->root->children as $child) {
          $buff = array();
          foreach($child->children as $lines) {
            $buff[$lines->getName()] = $lines->firstChild()->getText();
          }
          $this->buffer[$group][$child->getAttribute("tag")] = $buff;
        }
        //save cache file
        Cache::save('translationcache_translate_'.$group.'.php',serialize($this->buffer[$group]));
      }
      else{
        $this->buffer[$group] = $buff;
        unset($cache_buffer);
      }
    }
    return (isset($this->buffer[$group][$tag][$this->language]))?$this->buffer[$group][$tag][$this->language]:"#MISSING MESSAGE#";
  }
}

?>
