<?php
namespace Forge;

/**
 * Description of securityuser
 *
 * @author Darghon
 */
class SecureUser implements ISecurity{
	
	protected $credentials = array();
	protected $attributes = array();
	protected $flag = self::USER_GUEST;

	public function setUserMode($user_mode = self::USER_GUEST){
		$this->flag = $user_mode;
	}
	
	public function setCredentials(array $cred) {
		foreach($cred as $key => $value) $this->credentials[] = $value;
	}
	
	public function getCredentials() {
		return $this->credentials;
	}
	
	public function hasCredentials($cred) {
		return in_array($cred,$this->credentials);
	}
	
	public function getAttribute($attr, $default_value) {
		return isset($this->attributes[$attr]) || array_key_exists($attr, $this->attributes) ? $this->attributes[$attr] : $default_value;
	}
	
	public function setAttribute($attr, $value) {
		$this->attributes[$attr] = $value;
	}
	
	public function setAttributes(array $attr){
		foreach($attr as $key => $value) $this->setAttribute($key,$value);
	}
	
	public function destroy(){
		$this->credentials = array(); //flush credentials
		$this->attributes = array(); //flush all attributes
	}
	
	public function removeCredentials(array $cred) {
		$this->credentials = array_diff($this->credentials, $cred);
	}
	
	/**
	 * Standard destroy method
	 */
	public function __destroy() {
		foreach($this as $key => $value) unset($this->$key);
		unset($this);
	}
	
}