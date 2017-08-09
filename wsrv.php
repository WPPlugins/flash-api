<?php

error_reporting(E_ERROR | E_PARSE);
define('SITEROOT', '../../../');

require_once(SITEROOT . 'wp-config.php');
require_once(SITEROOT . 'wp-load.php');
require_once(SITEROOT . 'wp-includes/wp-db.php');

// Initiate
do_service(); 

/**
 * @desc Converts an associative array to an xml or json string
 * @param array:Array - an associative array
 */
function output($arr, $type = null) {
	if (!$type || $type == null) { $type = strtolower($_REQUEST['output']); }
	
	switch($type) { 
		case 'xml':
			$output = outputXML($arr); 
			break;
			
		case 'json':
		default: 
			$output = outputJSON($arr);
	}
	
	return $output;
}

/**
 * @desc converts an associative array into an xml string
 * @param array:Array - an associative array
 */
function outputXML($arr) { 
	require_once('xmlLib.php');
	header("Content-type: text/xml");
	$array = new ArrayToXML($arr);
	$xml = $array->getXML();
	$xml = '<?xml version="1.0" encoding="utf-8"?>' . "\r" . '<data>'.$xml.'</data>';
	$xml = str_replace('&lt;![CDATA[', '<![CDATA[', $xml);
	$xml = str_replace(']]&gt;', ']]>', $xml);
	$xml = str_replace('&lt;', '<', $xml);
	$xml = str_replace('&gt;', '>', $xml);
	return $xml;
}

/**
 * @param array
 * @desc converts an associative array into a json string
 */
function outputJSON($arr) { 
	$json = json_encode($arr);
	$json = str_replace('\/', '/', $json);
	$json = str_replace('<!--[CDATA[', '', $json);
	$json = str_replace('<![CDATA[', '', $json);
	$json = str_replace(']]-->', '', $json);
	$json = str_replace(']]>', '', $json);
	return $json;
}

/**
 * @desc Initiates the web service
 */
function do_service() {

	/**
	 * API KEY CHECK	
	 */
	global $wpdb;

	$sql = $wpdb->prepare("SELECT option_value from ".$wpdb->options." WHERE option_name = 'flash_api_key'");
	$apiKey = $wpdb->get_var($sql);
	$key = $_REQUEST['apiKey'];
	$service = $_REQUEST['service'];

	if ($key != $apiKey) {// key isn't global
		
		if (is_ApiUser($key) != true) { // key isn't user based
			echo output(error('apiKey', 'INVALID API KEY', true)); 
			return; 
		}
		else { // key was user based 
			$services = flash_api_functions();
			$incl = $services[$service];
			if (file_exists($incl)) { include_once($incl); }
 
			if (function_exists($service)) { echo $service(); }
			else { echo output(error('service', 'INVALID API CALL', true)); }
		}
	}

	/**
	 * FUNCTION EXECUTION 
	 */
	else { // key was global
		$services = flash_api_functions();
		$incl = $services[$service];
		if (file_exists($incl)) { include_once($incl); }
		
		if (function_exists($service)) { echo $service(); }
		else { echo output(error('service', 'INVALID API CALL', true)); }
	}
}

/**
 * @desc displays a formatted error message.
 * @param Parameter:String - The rest parameter where the error is found.
 * @param Message:String - The return message.
 * @param Status:String - The type of error message (i.e. 'true', 'warning', 'fatal').
 */
function error($param, $msg, $status) {
	$arr = array('data'=>array('error'=>$status, 'param'=>$param, 'msg'=>$msg));
	return $arr;
}

/**
 * @desc checks for a users individual apikey and domain.
 * @param Key:String - the users custom API key.
 */
function is_ApiUser($key) {
	global $wpdb;
	
	$domain = $_REQUEST['domain'];
	if (!$domain) { $domain = $_SERVER['HTTP_HOST']; }
		
	// query users
	$users = $wpdb->get_results("SELECT ID FROM $wpdb->users");
	foreach($users as $user) { 
		$ukey = get_user_meta($user->ID, 'apiKey', true);	
		$http = get_user_meta($user->ID, 'apiUrl', true);
		if ($http == $domain && $ukey == $key) { return true; }
	}
	
	return false;
}

?>