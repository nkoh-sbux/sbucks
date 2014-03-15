<?php

require_once 'HTTP/Request2.php';
require_once 'functions.php';
require_once 'class.mobile.php';
require_once 'class.email.php';
require_once 'class.starbucks.php';
require_once('class.person.php');

if ($_POST["manualentry"] != "yes") {
if (($_POST["getDetails"] != "Details") && ((!isset($_POST["cardnum"])) || (!isset($_POST["csc"])) || ($_POST["cardnum"]=='') || ($_POST["csc"]==''))) {
	echo '<html><body>';
	echo '<p>Enter card details</p>';
	echo '<form method="post">';
	echo '<p>Number: <input type="text" name="cardnum" value=""></p>';
	echo '<p>CSC: <input type="text" name="csc" value=""></p>';
	echo '<p>Month: <input type="text" name="mth" value="'.date('m').'"> (Birthday)</p>';
	echo '<p>Mobile: <input type="text" name="ccode" value="47"><input type="text" name="mobile" value="96667263"></p>';
//	echo '<p>Leave this as default. If it is broken, then replace with another one from: <a href="http://www.receivesmsonline.net/#number">Receive SMS Online</a></p>';
	echo '<p>Leave this as default. If it is broken, then replace with another one from: <a href="http://receive-sms-online.com/">HERE</a></p>';
	echo '<input type="submit" name="register" value="Register"> &nbsp; <input type="submit" name="checkBal" value="Balance"></form>';

	echo '<p>Retrieve card data</p>';
	echo '<form method="post"><p>Email: <input type="text" name="email"></p>';
	echo '<p>Password: <input type="password" name="pw" value="password123"></p>';
	echo '<p><input type="submit" name="getDetails" value="Details"></p></form>';
	echo '</body></html>';
	exit(0);
}
}

$http = new HTTP_Request2();
if ($_POST["cardnum"] != '') {
	$sb = new Starbucks($http,$_POST["cardnum"], $_POST["csc"]);
	echo "Processing card " . $_POST["cardnum"] . " with " . $_POST["csc"] . "...<br>";
} else
	$sb = new Starbucks($http,'0412100011148004','14545355');

if ($_POST["checkBal"] =='Balance') {
	$status = $sb->checkBalance();
	echo '<p>Card: ' . $_POST["cardnum"] . '</p>';
	echo '<p>Balance is: ' .  $status["Balance"] . '</p>';
	echo '<p>Last transaction is: '. $status["LastTx"] . '</p>';
	echo $status["Beverages"] . ' purchased.';
	$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . ",BALENQ\n";
	file_put_contents('logfile.txt', $record, FILE_APPEND);
	die();
}

if ($_POST["getDetails"] == "Details") {
	echo "Logging in with " . $_POST["email"];
	$results = $sb->checkDetails($_POST["email"], $_POST["pw"]);
	if ($results == false) { echo 'Wrong credentials'; die(); }

	echo '<p><img src="'.$results['cardphoto'].'"></p>';
	echo '<br>Card Number: ' . $results['cnmask'];
	echo '<br>Balance is: ' .  $results['balance'];
	echo '<br>Last transaction is: '. $results['lasttx'].'<br>';

	echo '<hr>';
	if ($results['freedrink'] <>'') echo '<p>Drinks - '.$results['freedrink'].'</p>';
	if ($results['freecake'] <>'') echo '<p>Free cake (birthday)</p>';
	echo '<hr>';

	echo '<p>Just in case you cannot remember your birthday, it is '.$results['bd_day'].' of '.$results['bd_mth'].', '.$results['bd_year'].'</p>';
	echo '<p><form method="GET">';
	echo '<b>Want to change DOB? Enter new values below...</b></p>';
	echo '<p>Month: <input type="text" name="month" value=""></p>';
	echo '<p><input type="submit" name="Btn" value="Change DOB"></p>';
	echo '</form></p>';
	echo '<p>Please wait a while for me to log out the session...</p>';
	ob_flush();
	flush();
	sleep(rand(3,12));  
	$sb->logout($results['jcookie']);
	echo '<p>DONE - <a href="/">Go back</a></p>';

	$record = $_POST["email"] . ',' . $_POST["pw"] . ',' . ",CHKDETAILS\n";
	file_put_contents('logfile.txt', $record, FILE_APPEND);
	die();
}

// SETUP AND CONFIGURE EMAIL
$em = new Email($http);

if ($_POST["manualentry"] == "yes") {
	$em->setEmailAddress($_POST["email"]);
	$sb->activateOTP($em->getEmailAddress(), $_POST["otp"], $_POST["vlink"]);	
	echo '<br>---DONE---';

	$record = $_POST["email"] . ',' . $_POST["otp"] . ',' . $_POST["vlink"] . ",MANUALOTP\n";
	file_put_contents('logfile.txt', $record, FILE_APPEND);
	die();
}

echo 'Email to be used is ' . $em->getNewAddress() . '<br>';
//$em->setEmailAddress('Liate1988@dayrep.com');

// SETUP AND CONFIGURE MOBILE NUMBER
$mob = new Mobile($http);
if (!isset($_POST["mobile"])) {
	echo 'Mobile to be used is ' . $mob->getNewMobile('49', '48', '47').'<br>';
} else {
	$mob->setMobileNumber($_POST["ccode"], $_POST["mobile"]);
	echo 'Mobile to be used is ' . $mob->getMobile() . '<br>';
}

$dob_year = 1900+rand(60, 98);

$status = $sb->checkCard();

switch ($status) {
	case -1: echo 'Invalid card or csc<br>';
		$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . $_POST["ccode"] . ',' . $_POST["mobile"] . ',' . $em->getEmailAddress() . ",INVALID\n";
		file_put_contents('logfile.txt', $record, FILE_APPEND);
		die(); break;
	case 0: echo 'Already registered<br>'; 
		$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . $_POST["ccode"] . ',' . $_POST["mobile"] . ',' . $em->getEmailAddress() . ",AlreadyRegistered\n";
		file_put_contents('logfile.txt', $record, FILE_APPEND);
		die(); break;
	case 1: echo 'Check card status Ok - can register<br>'; break;
	default: echo 'Error<br>'; die();
}

$person = new Person($http);
$person->getNewPerson();
$sb->registerCard($em->getEmailAddress(), $mob->getCountryCode(), $mob->getMobile(), '23', $_POST["mth"], $dob_year, $person);

echo 'Submitted registration form... Pls wait a while for the verification email to be sent...<br>';
ob_flush();
flush();
sleep(10);

echo 'Checking email at ' . $em->getEmailAddress() . '<br>';
if ($em->waitForEmail(30, 10)) {    //30 times @ 10 seconds
	$eid = $em->getLastEmailId();
	$vemail = $em->getEmailById($eid);
	$vlink = $sb->extractVerificationLink($vemail);
	if (!$sb->clickVerificationLink($vlink)) { echo 'Verification link failed.'; die(); };

	echo '<a target="_new" href="http://card.starbucks.com.sg/'.$vlink.'">Verification link</a> clicked. Waiting for SMS...<br>';
	echo 'If you wish to manually enter the OTP, type it in here: 
		<form method="POST">
		<input type="text" name="otp">
		<input type="hidden" name="manualentry" value="yes">
		<input type="hidden" name="email" value="'.$em->getEmailAddress().'">
		<input type="hidden" name="vlink" value="'.$vlink.'">
		<input type="submit" value="Manual OTP">
		</form>
		<br>';
	echo 'Check SMS Link: <a target="_new" href="'.$mob->getSMSURL().'">Click here</a></p>';
	ob_flush();
	flush();
	sleep(5);

	$smsresult = $mob->waitForSMS($person->name, '30', '5'); //30 times @ 5 seconds
	if ($smsresult === false) {
		$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . $_POST["ccode"] . ',' . $_POST["mobile"] . ',' . $em->getEmailAddress() . ",SMS-Timeout\n";
		file_put_contents('logfile.txt', $record, FILE_APPEND);
		die('Did not get SMS before timeout');
	} else {
		echo 'Received SMS with OTP -> [';
	}

	$otp = $sb->extractOTP($smsresult);
	echo $otp . ']<br>';
	$sb->activateOTP($em->getEmailAddress(), $otp, $vlink);	
}
else { 
	echo 'Waited but no email came'; 
	$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . $_POST["ccode"] . ',' . $_POST["mobile"] . ',' . $em->getEmailAddress() . ",EmailTimeout\n";
	file_put_contents('logfile.txt', $record, FILE_APPEND);
	die(); 
}

echo '<br>---DONE---';

$record = $_POST["cardnum"] . ',' . $_POST["csc"] . ',' . $_POST["ccode"] . ',' . $_POST["mobile"] . ',' . $em->getEmailAddress() . ",OK\n";
file_put_contents('logfile.txt', $record, FILE_APPEND);

?>
