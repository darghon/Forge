<?php
namespace Forge;

abstract class Components extends Actions {

	public function loadTemplate($app, $mod, $act) {
		$filepath = Config::path("app") . "/" . $app . "/modules/" . $mod . "/templates/_" . (($this->static_template != null) ? $this->static_template : $act) . ".template.php";
		if (file_exists($filepath)) {
			foreach ($this as $key => $value) {
				${$key} = $value;
			}
			require($filepath);
			return true;
		} else {
			trigger_error('Cannot locate template '.$filepath);
			return false;
		}
	}

}
