<?php
namespace Core;

/**
 * This class creates a connection to the database, by a loaded configuration file,
 * or by setting the user, pass, host and database manually
 */
class Connection {

	/**
	 * Private variables
	 */
	private $user = null;
	private $pass = null;
	private $host = null;
	private $database = null;
	private $connection = null;
	private $initiated = false;

	/**
	 * Constructor with optional config
	 */
	public function __construct($config = null) {
		if ($config !== null) {
			$this->user = $config["user"];
			$this->pass = $config["pass"];
			$this->host = $config["host"];
			$this->database = $config["database"];
		}
	}

	/**
	 * Set all variables at once with a config
	 */
	public function setConfig($config) {
		$this->user = $config["user"];
		$this->pass = $config["pass"];
		$this->host = $config["host"];
		$this->database = $config["database"];
	}

	/**
	 * All Standard Getters and setters, although there is no get for password, and should remain this way
	 * If it would be needed, only create a private function.
	 */
	public function setUser($user) {
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}

	public function setPass($pass) {
		$this->pass = $pass;
	}

	public function setHost($host) {
		$this->host = $host;
	}

	public function getHost() {
		return $this->host;
	}

	public function setDatabase($database) {
		$this->database = $database;
	}

	public function getDatabase() {
		return $this->database;
	}

	/**
	 * Returns the created connection to the requester.
	 */
	public function & getConnection() {
		if ($this->initiated === false)
			$this->createConnection(); //Create connection if it doesn't exists
		return $this->connection;
	}

	/**
	 * Private function that initiates the connection once all variables are filled in
	 */
	private function createConnection() {
		//Validate every needed input field
		if ($this->user !== null && $this->pass !== null && $this->host !== null && $this->database !== null) {
			$this->connection = mysql_connect($this->host, $this->user, $this->pass) OR die("Could not connect with user: {$this->user} and pass: {$this->pass}");
			mysql_query(sprintf('CREATE DATABASE IF NOT EXISTS %s',$this->database),$this->connection) || die(mysql_error());
			mysql_select_db($this->database, $this->connection) || die(mysql_error());
			$this->initiated = true;
		}
	}

	/**
	 * Standard destroy method that unsets every variable
	 */
	public function __destroy() {
		unset($this->user, $this->pass, $this->host, $this->database, $this->connection);
	}

}