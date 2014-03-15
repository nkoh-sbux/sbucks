<?php
require_once 'HTTP/Request2.php';
require_once 'functions.php';

class Email {

	//Properties
	private $emailaddress	= '';
	private $emailid	= '';
	private $emaildomain	= '';
	private $providerURL	= 'http://www.fakemailgenerator.com/';
	private $HTTP		= null;

	function __construct($obj_HTTP) {
		$this->HTTP = $obj_HTTP;
	}

	//Getters
	function getEmailAddress() {
		return $this->emailaddress;
	}

	function setEmailAddress($emailaddress) {
		$this->emailaddress = $emailaddress;
		$tokens = explode('@', $emailaddress);
		$this->emailid = $tokens[0];
		$this->emaildomain = $tokens[1];
	}

	function getNewAddress() {
		$this->HTTP->setURL($this->providerURL);
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}
		$page = $response->getBody();
		$this->setEmailAddress(get_string_between($page, '<span id="cxtEmail">', '</span>'));
		return $this->emailaddress;
	}

	function checkForNewEmail() {
		$this->HTTP->setURL($this->providerURL.'checkemail.php?u='.$this->emailid.'&d=%40'.$this->emaildomain);
		$this->HTTP->setBody();
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		$this->HTTP->setHeader(array(
			'Referer'    => $this->providerURL,
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			echo 'Exception in checkForNewEmail<br>';
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();

		if ($page=='true') {
			return true;
		} else {
			return false;
		}		
	}

	function waitForEmail($tries, $interval) {
		$newmail = false;
		$loopcount = 0;
		while (($newmail == false) && ($loopcount < $tries)) {
			$loopcount++;
			if ($this->checkForNewEmail() == true) {
				$newmail = true;
				echo 'Got mail! Working on it...<br>';
			}
			else {
				echo 'No new email...<br>';
				ob_flush();
				flush();
				sleep($interval);
			}
		}
		return $newmail;
	}

	function getLastEmailId() {

		$this->HTTP->setURL($this->providerURL.'inbox/'.strtolower($this->emaildomain).'/'.strtolower($this->emailid).'/');
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		$this->HTTP->setHeader(array(
			'Referer'    => $this->providerURL,
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();

		$emailid = get_string_between($page, 'src="http://www.fakemailgenerator.com/email.php?id=', '"></iframe>');

		return $emailid;
	}

	function getEmailById($eid) {
		$this->HTTP->setURL($this->providerURL.'email.php?id='.$eid);
		$this->HTTP->setHeader(array(
			'Referer'    => $this->providerURL,
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($ecx, true);
		}

		return $response->getBody();
	}

} //class

/* TESTING CODE */
/*******************
$http = new Http_Request2;
$email = new Email($http);

//$email->getNewAddress(); 
//echo $email->getEmailAddress();

$email->setEmailAddress('TInack2066@teleworm.us');
echo 'Polling ' . $email->getEmailAddress() . '<br>';

if ($email->waitForEmail(10, 6)) {
	$eid = $email->getLastEmailId();
	echo 'Last one is ' . $eid; 
	echo $email->getEmailById($eid);
}
else { echo 'Waited but no email came'; }
***************/
?>
