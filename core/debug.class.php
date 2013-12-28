<?php
namespace Core;

class Debug {

	protected static $enabled = false;

	public static function start() {
		self::addTimer('global');
		self::add('MEM_USE', 'Initial: ' . number_format(memory_get_usage() / 1024) . ' kb');
		return (self::$enabled = true);
	}

	public static function stop() {
		self::add('MEM_USE', 'Peak: ' . number_format(memory_get_peak_usage() / 1024) . ' kb');
		self::add('MEM_USE', 'End: ' . number_format(memory_get_usage() / 1024) . ' kb');
		self::stopTimer('global');
		return (self::$enabled = false);
	}

	public static function enabled() {
		return self::$enabled;
	}

	public static function Notice($type, $mess) {
		Forge::Log()->addNotice($type, $mess);
	}

	public static function Error($type, $mess) {
		Forge::Log()->addError($type, $mess);
	}

	public static function add($type, $mess) {
		Forge::Log()->addLog($type, $mess);
	}

	public static function & getLogByType($type) {
		return Forge::Log()->getLogByType($type);
	}

	public static function & getAllLogs() {
		return Forge::Log()->getAll();
	}

	public static function addTimer($tag, $start = true) {
		Forge::getTimer($tag, $start);
	}

	public static function startTimer($tag) {
		Forge::getTimer($tag)->start();
	}

	public static function stopTimer($tag) {
		Forge::getTimer($tag)->stop();
	}

	public static function showTimer($tag, $dur = 3) {
		return Forge::getTimer($tag)->getDuration($dur);
	}

	public static function & getTimers() {
		return Forge::getTimers();
	}

	public static function & getAlarmingTimers($from = 0.1) {
		$collection = &Forge::getTimers();
		$return = array();
		foreach ($collection as &$entry) {
			if ($entry->getDuration() >= $from)
				$return[] = & $entry;
		}
		return $return;
	}

	public static function placeDebugHolder() {
		$settings = Config::get("settings");
		if (isset($settings['debug']) && isset($settings['debug']['enabled']) && $settings['debug']['enabled'] == true) {
			echo "[DEBUG PLACEHOLDER-----[DEBUG]";
		}
	}

	public static function readSource($file) {
		//$file = fopen($file,'r');
		$content = file_get_contents($file); //fread($file,filesize($file));
		//fclose($file);
	}

	public static function registerError($errno, $errstr, $errfile, $errline) {
		$errlog = null;
		switch ($errno) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$errors = "Notice";
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$errors = "Warning";
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$errors = "Fatal Error";
				break;
			default:
				$errors = "Unknown";
				break;
		}

		Debug::add('Registered Error =>', sprintf("<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $errors, $errstr, $errfile, $errline));
		printf("<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $errors, $errstr, $errfile, $errline);
		//make sure we're not logging the same error 2x
		if (method_exists(DebugErrorLog::Find(), 'byMessage')) {
			$errlog = &DebugErrorLog::Find()->byMessage($errstr);
		} else {
			Debug::Notice('Notice', 'No "byMessage" finder has been specified for DebugErrorLog. A new record for each error will be made.');
		}
		if (!$errlog instanceOf DebugErrorLog || $errlog->getErrorFile() != $errfile || $errlog->getErrLine() != $errline) {
			$errlog = new DebugErrorLog();
		}
		if (class_exists('StageHandler') && StageHandler::getCurrentStage() >= 2) { //make sure Route is loaded before retrieving it's values
			$errlog->setApplication(Route::curr_app());
			$errlog->setModule(Route::curr_mod());
			$errlog->setAction(Route::curr_act());
			$errlog->setUrl(Route::curr_url());
		} else {
			$errlog->setUrl($_SERVER["REQUEST_URI"]);
		}
		$errlog->setSession(Session::serialize());
		$errlog->setErrorNr($errors);
		$errlog->setErrorMess($errstr);
		$errlog->setErrorFile(addslashes($errfile));
		$errlog->setErrLine($errline);
		$errlog->persist();
		return true;
	}

	public static function registerCrash() {
		$isError = false;
		$error = null;
		$errlog = null;
		if (false !== ($error = error_get_last())) {
			switch ($error['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$isError = true;
					break;
			}
		}

		if ($isError) {
			//make sure we're not logging the same error 2x
			//make sure we're not logging the same error 2x
			if (method_exists(DebugErrorLog::Find(), 'byMessage')) {
				$errlog = &DebugErrorLog::Find()->byMessage($errstr);
			} else {
				Debug::Notice('Notice', 'No "byMessage" finder has been specified for DebugErrorLog. A new record for each error will be made.');
			}
			if (!$errlog instanceOf DebugErrorLog || $errlog->getErrorFile() != $errfile || $errlog->getErrLine() != $errline) {
				$errlog = new DebugErrorLog();
			}
			if (class_exists('StageHandler') && StageHandler::getCurrentStage() >= 2) { //make sure Route is loaded before retrieving it's values
				$errlog->setApplication(Route::curr_app());
				$errlog->setModule(Route::curr_mod());
				$errlog->setAction(Route::curr_act());
				$errlog->setUrl(Route::curr_url());
			} else {
				$errlog->setUrl($_SERVER["REQUEST_URI"]);
			}
			$errlog->setSession(Session::serialize());
			$errlog->setErrorNr($error['type']);
			$errlog->setErrorMess($error['message']);
			$errlog->setErrorFile(addslashes($error['file']));
			$errlog->setErrLine($error['line']);
			$errlog->persist();
		}
		Debug::add('Registered Error =>', sprintf("<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $error['type'], $error['message'], $error['file'], $error['line']));
		printf("<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $error['type'], $error['message'], $error['file'], $error['line']);
	}

	public static function registerLongLoad($load = 0.5) {
		$timers = &self::getAlarmingTimers($load);
		foreach ($timers as &$timer) {
			$log = new DebugTimerLog();
			$log->setMessage($timer->getName());
			$log->setMs((int) ($timer->getDuration(4) * 1000));
			$log->setRank(max(1, (int) (10 - (int) ($timer->getDuration(4) * 10))));
			$log->persist();
		}
	}

}