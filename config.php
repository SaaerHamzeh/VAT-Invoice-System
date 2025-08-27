<?php
// Define protcol, hostname, full url to call the site, web root, and the site name & language
// define("PROTOCOL", "http://");
// define("HTTP_HOST", $_SERVER['HTTP_HOST']);
// define("FULL_URL", HTTP_HOST."/clarita_acc");
// define('WEB_ROOT', PROTOCOL.FULL_URL);
// define('SITE_NAME', "SACCP");

// Define database connection variables
// create database 'clarita_db';
// grant all on clarita_db.* to 'clarita_dbusr' identified by 'pass!g7';
define("DB_HOST", "localhost");
define("DB_NAME", "InvoicesDB");
define("DB_USER", "root");
define("DB_PASS", "");
//define("DB_USER", "root");
//define("DB_PASS", "mysql");
define("DB_DEBUG", true);// Show database errors
define("PER_PAGE", 25);
