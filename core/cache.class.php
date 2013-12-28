<?php
namespace Core;

class Cache {

	private static $enabled = null;
	private static $fpc_enabled = null;
	private static $folders_checked = false;

	public static function save($name, $content) {
		self::checkPath();
		$file = fopen(Config::path('cache') . '/' . $name, 'w');
		fwrite($file, '<?php $buffer = unserialize(stripslashes("' . addslashes($content) . '"));');
		fclose($file);
	}

	public static function load($name, $default = null, $bypass = false) {
		self::checkPath();
		if (!$bypass && !self::enabled())
			return $default;
		// && filemtime(Config::path('cache').$name) > strtotime(removeHours(date('Y-m-d H:i:s'), 1))
		if (file_exists(Config::path('cache') . '/' . $name)) {
			include(Config::path('cache') . '/' . $name);
			return $buffer;
		} else {
			return $default;
		}
	}
	
	/**
	 * Save a full page cache entry
	 * @param type $name
	 * @param type $content 
	 */
	public static function save_fpc($name, $content) {
		self::checkPath();
		file_put_contents(Config::path('cache') . '/' . $name,$content);
	}
	
	/**
	 * Load a full page cache entry
	 * @param type $name
	 * @param type $default
	 * @param type $bypass
	 * @return type 
	 */
	public static function load_fpc($name, $default = null) {
		self::checkPath();
		// && filemtime(Config::path('cache').$name) > strtotime(removeHours(date('Y-m-d H:i:s'), 1))
		if (file_exists(Config::path('cache') . '/' . $name) && filemtime(Config::path('cache').'/'.$name) > strtotime('-5 seconds')) {
			return file_get_contents(Config::path('cache').'/'.$name);
		} else {
			return $default;
		}
	}

	public static function saveOutput($name, $content) {
		self::checkPath();
		file_put_contents(Config::path('cache') . '/' . $name,$content);
		chmod(Config::path('cache') . '/' . $name, 0777);
	}

	public static function loadOutput($name, $default = false, $expires = 31536000) {
		self::checkPath();
		if (!self::enabled() || filemtime(Config::path('cache') . $name) > strtotime('-' . $expires . ' seconds'))
			return $default;
		if (file_exists(Config::path('cache') . '/' . $name)) {
			include(Config::path('cache') . '/' . $name);
			return true;
		} else {
			return $default;
		}
	}

	public static function addClassLocation($class, $location) {
		self::checkPath();
		$init = false;
		if (!file_exists(Config::path('cache') . '/classcache_list.php'))
			$init = true;
		$file = fopen(Config::path('cache') . '/classcache_list.php', 'a');
		if ($init)
			fwrite($file, "<?php" . PHP_EOL . "\$buffer = array();" . PHP_EOL);
		//add line to file
		fwrite($file, '$buffer["' . $class . '"] = "' . $location . '";' . PHP_EOL);
		fclose($file);
		unset($class, $location);
	}

	public static function loadClassArray() {
		return self::load('classcache_list.php', array(), true);
	}

	public static function unlinkClassArray() {
		self::checkPath();
		@unlink(Config::path('cache') . '/classcache_list.php');
	}

	public static function getCss($css = array()) {
		self::checkPath();
		$name = md5(implode('', $css));
		if (!file_exists(Config::path('webcache') . '/' . $name . '.cache.css')) {
			$buffer = self::mergeCss($css);
			/* remove comments */
			$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
			/* remove tabs, spaces, newlines, etc. */
			$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
			file_put_contents(Config::path('webcache'). '/' . $name . '.cache.css',$buffer);
		}
		return Config::path('webcache_url') . $name . '.cache.css';
	}

	public static function getJs($js = array()) {
		self::checkPath();
		$name = md5(implode('', $js));
		if (!file_exists(Config::path('webcache') . '/' . $name . '.cache.js')) {
			$buffer = self::mergeJs($js);
			file_put_contents(Config::path('webcache') . '/' . $name . '.cache.js',JSMin::minify($buffer));
		}
		return Config::path('webcache_url') . $name . '.cache.js';
	}

	public static function enabled() {
		if (self::$enabled === null) {
			$settings = Config::get('settings');
			self::$enabled = isset($settings["cache"]) ? $settings["cache"]["enabled"] : false;
		}
		return self::$enabled;
	}
	
	public static function enable(){
		self::$enabled = true;
		return true;
	}
	
	public static function disable(){
		self::$enabled = false;
		return true;
	}

	private static function mergeCss($css) {
		$content = '';
		foreach ($css as $entry) {
			$content .= file_get_contents(Config::path('css') . '/' . $entry).PHP_EOL;
		}
		return $content;
	}

	private static function mergeJs($js) {
		$content = '';
		foreach ($js as $entry) {
			$file = fopen(Config::path('js') . '/' . $entry, 'r');
			$content .= fread($file, filesize(Config::path('js') . '/' . $entry)) . "\n";
			fclose($file);
		}
		return $content;
	}

	private static function checkPath() {
		if(self::$folders_checked == false){
			//config & module cache
			if (!file_exists(Config::path('cache'))) {
				mkdir(Config::path('cache'));
			}
			//css & js cache
			if (file_exists(Config::path('web')) && !file_exists(Config::path('webcache'))) {
				mkdir(Config::path('webcache'));
			}
			self::$folders_checked = true;
		}
	}

	public static function fpc(){
		if (self::$fpc_enabled === null) {
			$settings = Config::get('settings');
			self::$fpc_enabled = isset($settings["full_page_cache"]) && $settings["full_page_cache"]["enabled"] == true ? $settings["full_page_cache"]["life_time"] : null;
		}
		return self::$fpc_enabled;
	}
	
}
