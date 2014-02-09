<?php
namespace Forge;


abstract class Actions {
    //Add the needed traits
    use EventListener,
        Translator;

	/**
	 * A force static template that needs to be loaded for the requested page.
	 * @var String 
	 */
	protected $static_template = "";


	
	/**
	 * Redirect he application to the specified location
	 * @param String $location can be a url, a module/action or a routing rule
	 * @param Array $param list of additional parameters
	 */
	public function redirect($location, $param = array()) {
		//echo "should be setting header location to: ".Config::path("url").Route::url($location, $param);
		Route::redirect($location, $param);
	}

	/**
	 * Forward the request to a different action of the loaded module without redirecting the page
	 * @param String $action
	 * @param String $template Optional non default template to be loaded
	 */
	public function forward($action, $template = "def") {
        $actionName = $action.'Action';
		if (method_exists($this, $actionName)) {
			if ($template != "def") {
				$this->changeTemplate($template);
			} else {
				$this->changeTemplate($action);
			}
			$this->{$actionName}();
		}
	}

	/**
	 * Translate all variables defined within the object scope onto the template that needs to be loaded
	 * All functions of the controller are available with $this
	 * @param String $app
	 * @param String $mod
	 * @param String $act
	 * @return Boolean $success
	 */
	public function loadTemplate($app, $mod, $act) {
		$filepath = Config::path("app") . "/" . $app . "/modules/" . $mod . "/templates/" . (($this->static_template != null) ? $this->static_template : $act) . ".template.php";
		if (file_exists($filepath)) {
			foreach ($this as $key => $value) {
				${$key} = $value;
			}
			require($filepath);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Specific hook to load a generated CRUD interface
	 * @param String $object_type
	 * @return Boolean $success
	 */
	public function loadAdminTemplate($object) {
		$filepath = Config::path("objects") . "/admin/" . $object . "/templates/index.template.php";
		if (file_exists($filepath)) {
			foreach ($this as $key => $value) {
				${$key} = $value;
			}
			require($filepath);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Force the loading of a specific template
	 * @param String $template
	 */
	public function changeTemplate($template) {
		$this->static_template = strtolower($template);
	}
}
