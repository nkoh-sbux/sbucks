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


?>
