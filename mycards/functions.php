<?php

/*
 * Shared functions
 */

error_reporting(E_WARN);
ini_set("display_errors", 1);

function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}

function process_HTTP_exception($exc, $terminate) {
	$es  = $exc->getTraceAsString();
	$ets = $exc->__toString();
	$egc = $exc->getCode();
	$egl = $exc->getLine();
	$egm = $exc->getMessage();
	$egt = $exc->getTrace();
	if ($terminate) {
		echo ('HTTP exception: ' . $egm . ' at '. $egl . '>>'.$egc.'<br>');
		print_r($egt);
		die();
	} else
		echo 'HTTP exception: ' . $egm . ' at '. $egl;
}

function getRemoteIP() {
	$ipaddress = '';
	if ($_SERVER['HTTP_CLIENT_IP'])
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if($_SERVER['HTTP_X_FORWARDED_FOR'])
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if($_SERVER['HTTP_X_FORWARDED'])
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if($_SERVER['HTTP_FORWARDED_FOR'])
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if($_SERVER['HTTP_FORWARDED'])
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';

	return $ipaddress;
}

function writeLog($message) {
	$LOGFILE = 'logfile.txt';
	file_put_contents($LOGFILE, '[' . date("Y-m-d H:i:s"). '] ' . $message, FILE_APPEND);
}
?>
