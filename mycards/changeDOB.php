<?php

require_once 'HTTP/Request2.php';
require_once 'functions.php';
require_once 'class.mobile.php';
require_once 'class.email.php';
require_once 'class.starbucks.php';

$http = new HTTP_Request2();
$sb = new Starbucks($http);

$email = '';
$pw = '';
$dd = rand(1,28);
$mm = '04';
$yy = 1970+rand(1,28);

$email	= $_GET["email"];
$pw	= $_GET["pw"];
if ($_GET["dd"] != '') $dd	= $_GET["dd"];
$mm	= $_GET["mm"];
if ($_GET["yy"] != '') $yy	= $_GET["yy"];

if (strlen($mm) == 1) $mm = '0'.$mm;

echo '<p>email, pw, dd, mm, yy are needed.</p>';
if (($email=='') || ($yy=='')) die();


$sb->changeCardDetails($email, $pw, $dd, $mm, $yy);

?>
