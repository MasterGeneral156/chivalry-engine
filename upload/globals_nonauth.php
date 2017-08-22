<?php
/*
	File:		globals_nonauth.php
	Created: 	4/5/2016 at 12:05AM Eastern Time
	Info: 		Calls all internal files/settings for when a user
				is not logged in.
	Author:		TheMasterGeneral
	Website: 	https://github.com/MasterGeneral156/chivalry-engine
*/
//If this file is opened directly.
if (strpos($_SERVER['PHP_SELF'], "globals_nonauth.php") !== false)
{
    exit;
}
//If theme isn't stored in cookie, set it to cookie.
if (!isset($_COOKIE['theme']))
{
	setcookie('theme',1,time()+86400);
	$_COOKIE['theme']=1;
}
$time=time();
//Set session name and start it.
session_name('CENGINE');
@session_start();
header('X-Frame-Options: SAMEORIGIN');
//If session is not started, regenerate ID and load it.
if (!isset($_SESSION['started']))
{
    session_regenerate_id();
    $_SESSION['started'] = true;
}
ob_start();
//Require the error handler.
require "lib/basic_error_handler.php";
set_error_handler('error_php');
//Require styling.
require "header_nonauth.php";
include "config.php";
define("MONO_ON", 1);
//Connect to database.
require "class/class_db_{$_CONFIG['driver']}.php";
require_once('global_func.php');
$db = new database;
$db->configure($_CONFIG['hostname'], $_CONFIG['username'],
        $_CONFIG['password'], $_CONFIG['database'], $_CONFIG['persistent']);
$db->connect();
$c = $db->connection_id;
//Update data in-game externally.
check_data();
//Include API file.
include("class/class_api.php");
$api = new api;
$set = array();
$settq = $db->query("SELECT *
					 FROM `settings`");
//Settings in friendly variables.
while ($r = $db->fetch_row($settq))
{
    $set[$r['setting_name']] = $r['setting_value'];
}
//Parse the headers.
$h = new headers;
$h->startheaders();
//Run the crons if possible.
foreach (glob("crons/*.php") as $filename) 
{ 
    include $filename; 
}