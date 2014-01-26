<?php
namespace Forge;

/**
 * Security Handler checks if the requested action is allowed for the current system actor.
 * Initialize loads all security settings
 * Deploy checks if the access is allowed
 * 
 * Security loads the security that is needed for the request.
 * The loaded security indicates if the request is_secure or not.
 * If so, the logged on user will be validated to check if they have the required credentials
 * If not, the auth_module and auth_action will be loaded as an alternative
 *
 * @author gerry.vanbael
 */
class SecurityHandler implements IStage {

	protected $is_secure = null;
	protected $cred_required = null;
	protected $auth_module = null;
	protected $auth_action = null;
	protected $credentials = null;
	protected $security_class = null;
	protected $variables = array();
	
	/**
	 * Internal variable that contains a reference of the logged in user which is retrieved from the session
	 * @var SecureUser 
	 */
	protected $_user = null;

	public function __construct() {
		
	}

	public function initialize() {
		//load security configuration
		$security = Config::get('security');
		$app_security = Config::get("security", "/application/" . Route::curr_app() . "/config/");
		$mod_security = Config::get("security", "/application/" . Route::curr_app() . "/modules/" . Route::curr_mod() . "/config/");
		
		if (isset($security['global']) || array_key_exists('global', $security)) $this->parseConfig($security['global']);
		if (isset($app_security['global']) || array_key_exists('global', $app_security)) $this->parseConfig($app_security['global']);
		if (isset($app_security[Route::curr_mod()]) || array_key_exists(Route::curr_mod(), $app_security)) $this->parseConfig($app_security[Route::curr_mod()]);
		if (isset($mod_security['global']) || array_key_exists('global', $mod_security)) $this->parseConfig($mod_security['global']);
		if (isset($mod_security[Route::curr_act()]) || array_key_exists(Route::curr_act(), $mod_security)) $this->parseConfig($mod_security[Route::curr_act()]);

		$this->_user = &Session::getActiveUser();
		if($this->_user === null){
            if(!class_exists($this->security_class)){
                Debug::add('NOTICE', 'Failed to load security class '.$this->security_class);
                return true;
            }
			$user = new $this->security_class;
			Session::setActiveUser($user);
			$this->_user = &Session::getActiveUser();
		}
		
		return true;
	}

	public function deploy() {
		if($this->is_secure && ($this->_user === null || !$this->_user->hasCredentials($this->cred_required))) return $this->notAllowed();
		return true;
	}

	private function parseConfig($config) {
		if (is_array($config)) {
			foreach ($config as $index => $entry) {
				if ($entry === null) continue;
				if(property_exists($this, $index)){
					$this->$index = $entry;
				}
				else{
					$this->variables[$index] = $entry;
				}
			}
		}
	}
	
	protected function notAllowed(){
		Route::redirect($this->auth_module.'/'.$this->auth_action);
	}

	public function __destroy() {
		foreach ($this as $key => $value)
			unset($this->$key);
		unset($this);
	}

}

?>
