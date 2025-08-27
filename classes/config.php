<?php
/* Include main configuration file */
// require_once(DOC_ROOT . "/config.php");
require("./config.php");

/* Define main variables if they are not defined in main config file */
if (!defined("DB_HOST"))		define("DB_HOST", "localhost");
if (!defined("DB_NAME"))		define("DB_NAME", "InvoicesDB");
if (!defined("DB_USER"))		define("DB_USER", "root");
if (!defined("DB_PASS"))		define("DB_PASS", "");
if (!defined("DB_DEBUG"))	define("DB_DEBUG", true);
if (!defined("PER_PAGE"))		define("PER_PAGE", 25);

class Config
{
	// Singleton object.
	private static $me;

	// Auth Conf
	public $authDomain;         // Domain name for cookies
	public $useHashedPasswords; // Store hashed passwords in database
	// Database Conf
	public $dbHost;
	public $dbName;
	public $dbUsername;
	public $dbPassword;
	public $dbDieOnError;		// What to do on a database error
	public $perPage;
	public $sessionName;

	// Singleton constructor
	private function __construct()
	{
		$this->authDomain = $_SERVER['HTTP_HOST'];
		$this->useHashedPasswords = true;
		$this->sessionName = 'saccp';

		ini_set('display_errors', 	'1');
		ini_set('error_reporting', E_ALL);
		$this->dbHost = DB_HOST;
		$this->dbName = DB_NAME;
		$this->dbUsername = DB_USER;
		$this->dbPassword = DB_PASS;
		$this->dbDieOnError = DB_DEBUG;
		$this->perPage = PER_PAGE;
	}

	// Get singleton object
	public static function getConfig()
	{
		if (is_null(self::$me))
			self::$me = new Config();
		return self::$me;
	}

	// Access config settings statically, like Config::get('key')
	public static function get($key)
	{
		return self::$me->$key;
	}
}
