<?php
/**************
 * Process all Starbucks card requests
 **************/

class Starbucks {

	//Properties
	private $sbURL		= 'https://card.starbucks.com.sg/';
	private $HTTP		= null;
	private $sCookies	= null;
	private $cardNo		= null;
	private $csc		= null;
	private $fname		= 'Princessy';

	function __construct($obj_HTTP, $card, $csc) {
		$this->HTTP = $obj_HTTP;
		$this->HTTP->setConfig(array(
			'ssl_verify_peer'   => FALSE,
			'ssl_verify_host'   => FALSE
		));
		$this->cardNo = $card;
		$this->csc = $csc;
	}

	function checkCard() {
		$this->HTTP->setURL($this->sbURL.'custProfile');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'register-your-card.jsp',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'BXC=; RXC-CLIENT='
		));

		$this->HTTP->addPostParameter(array(
			'prevUri'	=> 'register-your-card.jsp',
			'requestType'	=> 'REGISTER_CARD',
			'cardNo'	=> $this->cardNo,
			'csc'		=> $this->csc,
			'otpFlag'	=> 'null',
			'mode'		=> 'null'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$this->sCookies = $response->getCookies();
		//echo $this->sCookies[0]['value'];
		$page = $response->getBody();
		if (strpos($page, 'Invalid Card Number')) return -1;
		if (strpos($page, 'Card is already registered.')) return 0;
		return 1;
	}

	function registerCard($email, $ccode, $mobile, $day, $mth, $year, $person) {

		echo '<p>Name to be used is '.$person->name.'</p>';
		$this->HTTP->setURL($this->sbURL.'custProfile?requestType=REGISTER');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'create-account.jsp',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'BXC=; RXC-CLIENT='
		));

		$this->HTTP->addPostParameter(array(
			'prevUri'	=> 'create-account.jsp',
			'requestType'	=> 'REGISTER_CARD',
			'fName'		=> $person->name,
			'lName'		=> 'Shy',
			'nationality'	=> 'Barbadian',
			'gender'	=> 'F',
			'email'		=> $email,
			'password'	=> sha1('password123'),
			'password_cfm'	=> sha1('password123'),
			'countryCode'	=> $ccode,
			'mobileNumber'	=> $mobile,
			'country'	=> 'United States of America',
			'address'	=> $person->address,
			'postalCode'	=> $person->postalcode,
			'incomeBracket'	=> 'Below $2,000',
			'cardName'	=> 'TheFunkyCard',
			'cardNum'	=> $this->cardNo,
			'cardNo'	=> $this->cardNo,
			'csc'		=> $this->csc,
			'dob_day'	=> $day,
			'dob_mth'	=> $mth,
			'dob_year'	=> $year,
			'sbux_none'	=> 'Y',
			'sbux_Termsandconditions'	=> 'Y',
			'create-an-account'		=> 'Create an Account',
			'functionId'	=> '',
			'actionId'	=> '',
			'userId'	=> '',
			'otpFlag'	=>'null',
			'mode'		=>'null'
		));
	
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		if (strpos($page, 'Account Is Already Registered For This Email-Address') !== false) {
			echo 'Detected previous attempt to register, stopping process...';
			die();
		}

//		echo $page;
//file_put_contents('theresult.html', $page);

	}

	function extractVerificationLink($email) {
		//sample is
		//http://card.starbucks.com.sg/otp-activate-account.jsp?email=9BFE8448629C89023A0DD1BE136681B03E84DAF733487BC6&&flag=76D03F3A7849BC49&&random=F0359536284861991356136465BE20B2&&rt=RSF 

		$link = get_string_between($email, 'otp-activate-account.jsp?email=', '"');
		$link = 'otp-activate-account.jsp?email='.$link;
		return $link;
	}

	function clickVerificationLink($link) {
		$this->HTTP->setURL($this->sbURL.$link);
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);

		$this->HTTP->setHeader(array(
			'Referer'	=> '',
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		$this->sCookies = $response->getCookies();
		
		//echo $page;

		if (strpos($page, 'Enter Your One-Time-Password To Complete Your Starbucks Member Account Reactivation') !== false)
			return true;
		else
			return false;
	}

	function extractOTP($sms) {
		$otp = trim(get_string_between($sms, 'Password:', 'via'));
		return substr($otp, 0, 6);
	}

	function activateOTP($email, $otp, $vlink) {
		$this->HTTP = new HTTP_Request2();
		$this->HTTP->setConfig(array(
			'ssl_verify_peer'   => FALSE,
			'ssl_verify_host'   => FALSE
		));
		$this->HTTP->setURL('http://card.starbucks.com.sg/custProfile');
//		$this->HTTP->setURL('http://localhost:81/custProfile');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);

		$this->HTTP->setHeader(array(
			'Accept'	=> 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp',
			'Accept-Language' => 'en-GB,en-US;q=0.8,en;q=0.6',
			'Cache-Control'	=> 'max-age=0',
			'Origin'	=> 'http://card.starbucks.com.sg',
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Accept-Encoding' => 'gzip,deflate,sdch'
		));

		$this->HTTP->addPostParameter(array(
			'otpFlag'	=> 'RSF',
			'requestType'	=> 'ACTIVATE_ACCOUNT',
			'otpPin'	=> $otp,
			'emailId'	=> strtolower($email)
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		//file_put_contents('otpresults.html', $page);

		echo '<br>----------------<br>';
		if (strpos($page, 'Invalid One-Time-Password (OTP) provided') !== false) { die ('Invalid OTP'); return false; }
		if (strpos($page, 'Resubmit Your Profile') !== false) { die('Wrong landing page'); return false; }
		if (strpos($page, 'OTP has expired! Please register your Card again.') !== false) { die('Expired OTP'); return false; }
		if (strpos($page, 'Registration Successful') !== false) { echo 'SUCCESS'; return true; }
		echo 'Unknown result';
		return true;		
	}

	function checkBalance() {
		$this->HTTP->setURL('https://card.starbucks.com.sg/custProfile');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);

		$this->HTTP->setHeader(array(
			'Referer'    => 'https://card.starbucks.com.sg/check-your-balance.jsp',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Origin' => 'https://card.starbucks.com.sg',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));

		$this->HTTP->addPostParameter(array(
			'cardNo' => $this->cardNo, 
			'csc' => $this->csc,
			'requestType' => 'CARD_BALANCE',
			'functionId' => 'NonRegBal',
			'actionId' => 'NonRegBal',
			'prevUri' => 'check-your-balance.jsp'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();

		$sector_balance = get_string_between($page, '>My Card Balance<', '</table>');
		$balance= get_string_between($sector_balance, '<td style="text-align:right;">', '</td>');
		$lasttx= get_string_between($sector_balance, '</td><td>', '</td>');
		$sector_cups = get_string_between($page, 'is on us</td><td >', ' purchased');
		
		$results = array('Balance' => $balance, 'LastTx' => $lasttx, 'Beverages' => $sector_cups);		
		return $results;
	}

	function checkDetails($email, $pw) {

		$this->HTTP->setURL('https://card.starbucks.com.sg/custProfile');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'    => 'https://card.starbucks.com.sg/signin.jsp',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Origin' => 'https://card.starbucks.com.sg',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie' => 'BXC=; RXC-CLIENT='
		));

		$this->HTTP->addPostParameter(array(
			'prevUri'	=>'signin.jsp',
			'requestType'	=>'LOGIN',
			'emailId'	=>$email,
			'password1'	=>$pw,
			'password'	=>sha1($pw),
			'functionId'	=>'SignIn',
			'actionId'	=>'SignIn',
			'userId'	=>$email,
			'RXC'		=>'1'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		if (strpos($page, 'Invalid Email/Password provided') !== false) { return false; }

		$cookies = $response->getCookies();
		$jcookie = $cookies[0]['value'];
		//echo '<p>Logged in. Cookie is ' . $jcookie . '</p>';

		$this->HTTP->setURL('https://card.starbucks.com.sg/account_profile_balance.jsp?cNo=null&RXC=1');
		$this->HTTP->setBody('');

		$this->HTTP->setHeader(array(
			'Referer'	=> 'https://card.starbucks.com.sg/account_transaction.jsp?cNo=null&RXC=1',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Origin' => 'https://card.starbucks.com.sg',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie' => 'JSESSIONID='.$jcookie.'; BXC=; LOCAL-JSESSIONID='.$jcookie.'; RXC-CLIENT=1'
		));
		$this->HTTP->addPostParameter('RXC=1');

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();

		$cardphoto = get_string_between($page, 'Image?fid=/cardface', '"');
		$cardphoto = 'http://card.starbucks.com.sg/Image?fid=/cardface'.$cardphoto;

		$cnmask = get_string_between($page, '<span class="number">', '</span>');
		$sector_balance = get_string_between($page, '<div id="card-details-partial" class="details">', '</table>');
	
		$balance= get_string_between($sector_balance, '<tr><td  style="text-align:right;">', '</td>');
		$lasttx= get_string_between($sector_balance, '</td><td>', '</td>');

		$sector_rewards = get_string_between($page, '<b>Program</b>', 'rounded-footer');
		//$this->HTTPewards_none= get_string_between($sector_rewards , '<b>', '</b>');
		if (strpos($sector_rewards, 'Registration Complimentary Beverage Any Size') !== false) $rewards_freedrink = 'Registration Complimentary Beverage Any Size';
		if (strpos($sector_rewards, 'Complimentary Birthday Cake') !== false) $rewards_freecake = 'Complimentary Birthday Cake';
		//if ($rewards_none <> '') echo 'No rewards';

		$this->HTTP->setURL($this->sbURL.'account_change_profile.jsp?RXC=1');
		$this->HTTP->setBody();
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'custProfile?requestType=UPDATE_PROFILE',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'JSESSIONID='.$jcookie.'; BXC=; LOCAL-JSESSIONID='.$jcookie.'; RXC-CLIENT=1'
		));
		$this->HTTP->addPostParameter('RXC=1');
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}
		$page = $response->getBody();
		//echo $page;
		$section_bd = get_string_between($page, 'select id="dob_day"', '</select>');
		$bd_day = trim(get_string_between($section_bd, 'selected >', '<'));

		$section_bd = get_string_between($page, 'select id="dob_mth"', '</select>');
		$bd_mth = trim(get_string_between($section_bd, 'selected >', '<'));

		$section_bd = get_string_between($page, 'select id="dob_year"', '</select>');
		$bd_year = trim(get_string_between($section_bd, 'selected >', '<'));

		//STORE RESULTS INTO ARRAY
		$results = array(
			'cardphoto'	=> $cardphoto,
			'cnmask' 	=> $cnmask,
			'balance'	=> $balance,
			'lasttx'	=> $lasttx,
			'freedrink'	=> $rewards_freedrink,
			'freecake'	=> $rewards_freecake,
			'bd_day'	=> $bd_day,
			'bd_mth'	=> $bd_mth,
			'bd_year'	=> $bd_year,
			'jcookie'	=> $jcookie
		);

		return $results;
	}

	function logout($jcookie) {
		//LOG OUT
		$this->HTTP->setURL($this->sbURL.'custProfile?requestType=LOGOUT');
		$this->HTTP->setBody('');
		$this->HTTP->addPostParameter('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'account_transaction.jsp?cNo=null&RXC=1',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'JSESSIONID='.$jcookie.'; BXC=; LOCAL-JSESSIONID='.$jcookie.'; RXC-CLIENT=1'
		));	
	
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}
		return true;
	}

	function changeCardDetails($email, $pw, $day, $mth, $year) {
		if ($day=='') $day = rand(1,28);
		if ($year=='') $year = 1950+rand(1,50);

		//GET SESSION COOKIE BY LOGGING IN
		$this->HTTP->setURL('https://card.starbucks.com.sg/custProfile');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'    => 'https://card.starbucks.com.sg/signin.jsp',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Origin' => 'https://card.starbucks.com.sg',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie' => 'BXC=; RXC-CLIENT='
		));

		$this->HTTP->addPostParameter(array(
			'prevUri'	=>'signin.jsp',
			'requestType'	=>'LOGIN',
			'emailId'	=>$email,
			'password1'	=>$pw,
			'password'	=>sha1($pw),
			'functionId'	=>'SignIn',
			'actionId'	=>'SignIn',
			'userId'	=>$email,
			'RXC'		=>'1'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		if (strpos($page, 'Invalid Email/Password provided') !== false) { echo '<p>Wrong credentials</p>'; die(); }

		$cookies = $response->getCookies();
		$jcookie = $cookies[0]['value'];
		echo '<p>Logged in. Cookie is ' . $jcookie . '</p>';

		ob_flush();
		flush();

		//DO THE CHANGE NOW
		$this->HTTP->setURL($this->sbURL.'custProfile?requestType=UPDATE_PROFILE');
		$this->HTTP->setBody('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_POST);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'account_change_profile.jsp?RXC=1',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'JSESSIONID='.$jcookie.'; BXC=; RXC-CLIENT='
		));

		$this->HTTP->addPostParameter(array(
			'prevUri'	=> 'account_profile.jsp',
			'salutation'	=> '',
			'fName'		=> 'Power Puff',
			'lName'		=> 'Girls',
			'nationality'	=> 'Barbadian',
			'gender'	=> 'F',
			'country'	=> 'Ukraine',
			'address'	=> 'Some funky road',
			'postalCode'	=> '23588',
			'incomeBracket'	=> 'Below $2,000',
			'dob_day'	=> $day,
			'dob_mth'	=> $mth,
			'dob_year'	=> $year,
			'functionId'	=> 'UpdProfile',
			'actionId'	=> 'UpdProfile',
			'RXC'		=> '1'
		));
		echo '<p>Updating to ' . $day . '-' . $mth . '-' . $year.'...</p>';

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		if (strpos($page, 'updated successfully')) echo '<p>Update SUCCESS</p>'; else { die('Update FAILED'); }

		//LOG OUT NOW
		echo '<p>Please wait a while for me to log out the session...</p>';
		ob_flush();
		flush(); 
		sleep(rand(3,12));	
		//LOG OUT
		$this->HTTP->setURL($this->sbURL.'custProfile?requestType=LOGOUT');
		$this->HTTP->addPostParameter('');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		$this->HTTP->setHeader(array(
			'Referer'	=> $this->sbURL.'account_transaction.jsp?cNo=null&RXC=1',
			'Content-Type'	=> 'application/x-www-form-urlencoded',
			'Origin'	=> $this->sbURL,
			'User-Agent'	=> 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36',
			'Cookie'	=> 'JSESSIONID='.$jcookie.'; BXC=; LOCAL-JSESSIONID='.$jcookie.'; RXC-CLIENT=1'
		));	
	
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		echo '<p>DONE - <a href="/">Go back</a></p>';

		return true;
	}

} //Starbucks class
?>
