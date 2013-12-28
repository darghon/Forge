<?php
namespace Core;

class RouteHandler implements IStage {

	private $application = null;
	private $module = null;
	private $action = null;
	private $param = array();
	private $curr_url = null;
	private $routes = array();
	private $route_hooks = array();

	public function __construct() {}

	public function initialize() {
		//get valid url data
		$this->loadCurrentUrl();
		$this->loadDefaults();
		$this->loadRoutingRules();
	}

	public function deploy() {
		//get best matching rule for current url
		$best = null;
		$score = -1;
		foreach($this->routes as &$route){
			$s = $route->matchToUrl($this->curr_url);
			if($s > $score){
				$score = $s;
				$best = &$route;
			}
		}
		$this->processUri($best);

        if(!file_exists(Config::path('app').DIRECTORY_SEPARATOR .$this->application)){
            echo "<h1>Welcome to Forge</h1>";
            echo "<p>No applications have been defined.<br />Please define your application first by executing the build:application command from the forge script.</p>";
            exit;
        }
	}
	
	private function loadCurrentUrl(){
		//clear any php pages from url, they shouldn't even be there...
		$this->curr_url = preg_replace("/\/[^\/\.]*\.php/","",$_SERVER["REQUEST_URI"]);
		if (Config::path("sub_dir") != "/") {
			$this->curr_url = str_replace(Config::path("sub_dir"), "", $this->curr_url);
		}
		if ($this->curr_url == "") $this->curr_url = "/";
	}
	
	private function loadDefaults(){
		$settings = Config::get('settings');
		$this->application = isset($settings['default_application']) ? $settings['default_application'] : null;
		$this->module = isset($settings['default_module']) ? $settings['default_module'] : null;
		$this->action = isset($settings['default_action']) ? $settings['default_action'] : null;
		$this->route_hooks = array(
            'routing_service' => isset($settings['routing_service']) ? $settings['routing_service'] : null,
            'routing_component' => isset($settings['routing_component']) ? $settings['routing_component'] : null
        );
	}
	
	private function loadRoutingRules() {
		$rules = Config::get("routing");
		//apply rules
		foreach ($rules as $rule_name => $rule) {
			$this->routes[$rule_name] = new RoutingRule($rule);
		}
	}

	public function getApplication() {
		return $this->application;
	}

	public function getModule() {
		return $this->module;
	}

	public function getAction() {
		return $this->action;
	}

	public function getParam() {
		return $this->param;
	}

	public function getURL() {
		return $this->curr_url;
	}

	private function processUri(RoutingRule &$route){
		$request_url = Tools::cleanExplode('/', $this->curr_url);
		$rule_url = Tools::cleanExplode('/', $route->getRawUrl());
		$url_key = null;
		//set all routing params
		$attributes = $route->getAttributes();
		foreach($attributes as $key => $value){
			$this->setParam($key, $value);
		}
		foreach($request_url as $index => $request_part){
			if((isset($rule_url[$index]) || array_key_exists($index,$rule_url)) && $rule_url[$index] != '*'){
				//also exists in raw url, see if it's a parameter, if so, register the param. ignore otherwise
				if(preg_match("/\{\{([^\}]*)\}\}/",$rule_url[$index],$variable) != 0){
					list($key,$default) = explode(':',$variable[1]) + array(null,null);
					$this->setParam($key,$request_part);
					unset($match);
				}
			}
			else{
				//additional things should be paired as variables
				if($url_key === null){
					$url_key = $request_part;
				}
				else{
					$this->setParam($url_key,$request_part);
					$url_key = null;
				}
				
			}
		}
	}

	public function url_from_rule($rule, $param = array()) {
		if (isset($this->routes[$rule])) {
			$rule = $this->routes[$rule];
		} else {
			return null;
		}
		return Config::path('url') .'/'. $rule->buildUrl($param) . (isset($param['#']) ? '#' . $param['#'] : '');
	}

	/**
	 * This functions expects one of the following:
	 * a true url,
	 * a reference to a module/action or
	 * a rule name
	 * @param String $for
	 * @param Array $param
	 * @return String url
	 */
	public function url($for, $param = array()) {
		//First check what type of url is passed
		if (substr($for, 0, 7) == "http://" || $for == 'javascript:;') { //valid link
			return $for;
		}

		//else get all parts of passed url
		$uri = Tools::cleanExplode("/", $for);
		if (count($uri) == 1) return $this->url_from_rule($for, $param);
		
		//now the complex stuff, $for now must be module/action
		$vars = array();
		$vars["module"] = $uri[0]; //set module to vars array
		$vars["action"] = $uri[1]; //set action to vars array
		foreach ($param as $key => $entry) {
			$vars[$key] = $entry; //set other passed parameters
		}

		//time to select a corresponding rule
		$rtc = $this->routes; //make a local copy of routes
		foreach ($rtc as $key => $route) {
			//first check all parameters, if module and / or action parameter is set, then route can continue
			$ok = true; //assume route is ok
			$attributes = $route->getAttributes();
			foreach ($attributes as $name => $value) {
				if (!$ok) continue; //route already failed
				if ($name == "application" && $value != Route::curr_app()) $ok = false; //make sure that non-tagged linking remains in the active application
				if ($name == "module" && $value != $vars["module"]) $ok = false; //invalid module, route failes
				if ($name == "action" && $value != $vars["action"]) $ok = false; //invalid action, route failes
			}
			if (!$ok) {
				unset($rtc[$key]); //if it failed, it will be unset
				continue; //next in line
			}
		}
		//all that is left now, are routing rules that allow the passed module/action combo
		$best_rule = $this->getBestRule($rtc);
		
		$hash = null;
		if(isset($param['#']) || array_key_exists('#',$param)){
			$hash = $param['#'];
			unset($param['#']);
		}
		//return the result
		return Config::path('url') .'/'. $best_rule->buildUrl($vars) . "/" . (isset($hash) ? '#' . $hash : '');
	}

	public function link($text, $link, $param = array(), $linkparam = array()) {
		$return = "<a href='" . $this->url($link, $param) . "'";
		foreach ($linkparam as $key => $par) {
			$return .= " " . $key . "=\"" . $par . "\"";
		}
		$return .= ">" . $text . "</a>";
		return $return;
	}

	private function setParam($key, $value) {
		switch ($key) {
			case 'application':
				$this->application = $value;
				break;
			case 'module':
				$this->module = $value;
				break;
			case 'action':
				$this->action = $value;
				break;
			default:
				$this->param[$key] = $value;
				break;
		}
		//register param in request
		Request::setParameter($key,$value);
	}

	/**
	 * Arrange routing rules to most accurate first
	 * @param Array $rules
	 */
	private function getBestRule($rules) {
		if(count($rules) == 1) return $rules[key($rules)];
		$result = array();
		foreach ($rules as $key => $rule) {
			$result[$key] = $rule->getAttrInUrl();
		}
		asort($result);
		reset($result);
		return $rules[key($result)];
	}

	public function __destroy() {
		unset($this->application, $this->module, $this->action, $this->curr_url);
	}

}

?>
