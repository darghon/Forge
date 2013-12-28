<?php
namespace Core;
/**
 * Any class that implements this interface can be registered as a session handler
 * @author Darghon
 */
interface ISessionHandler {
	/**
	 * The Open method will be used to initialise the session.
	 * @return Boolean $success
	 */
	public function open($save_path, $session_name);
	/**
	 * The Close method will be used to close the session
	 * @return Boolean $success
	 */
	public function close();
	/**
	 * The Get method will be used to retrieve a value from the session
	 * @param String $session_id
	 * @return Object $session
	 */
	public function get($session_id);
	/**
	 * The Set method will be used to set a value from the session
	 * @param String $session_id
	 * @param Mixed $data
	 * @return Boolean $success
	 */
	public function set($session_id, $data);
	/**
	 * The Destroy method will be used to destroy an active session
	 * @return Boolean $success
	 */
	public function destroy($session_id);
	/**
	 * The Clean method will be used to clean the current session
	 * This function will be called by the garbage collector to clear out abandoned sessions
	 * This function should clean out ALL sessions, and not just the current one.
	 * @return Boolean $success
	 */
	public function clean($maxlifetime);
	
}

?>
