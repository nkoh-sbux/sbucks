<?php
require_once 'HTTP/Request2.php';
require_once 'functions.php';

class Mobile {

	//Properties
	private $mobileNum	= '';
	private $ccode		= '';
	private $mobile		= '';

//	private $provider	= 'RFS';
//	private $providerURL1	= 'http://receivefreesms.com/';
//	private $providerURL2	= 'http://www.receivesmsonline.net/receive-sms-online-vip-';

	private $provider	= 'RSO';
	private $providerURL1	= 'http://receive-sms-online.com/';
	private $providerURL2	= 'http://sms-verification.com/rec/%2B';

	private $smsAnchor	= 'Instagram';
//	private $smsAnchor	= 'Dear ';
	private $HTTP		= null;

	function __construct($obj_HTTP) {
		$this->HTTP = $obj_HTTP;
	}

	//Getters
	function getMobileNumber($addPlus) {
		if ($addPlus) 
			return '+' . $this->mobileNum;
		else
			return $this->mobileNum;
	}

	function getCountryCode() {
		return $this->ccode;
	}

	function getMobile() {
		return $this->mobile;
	}

	function setMobileNumber($ccode, $mobile) {
		$this->ccode = $ccode;
		$this->mobile = $mobile;
		$this->mobileNum = $ccode.$mobile;
	}

	function getSMSURL() {

		if ($this->provider == 'RFS')
			$dURL = $this->providerURL2.$this->mobileNum.'.html';
		elseif ($this->provider == 'RSO') 
			$dURL = $this->providerURL2.$this->mobileNum.'.php';
		else die('Critical error');

		return $dURL;
	}

	function getNewMobile($prefix1, $prefix2, $prefix3) {
		$this->HTTP->setURL($this->providerURL1);
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}
		$page = $response->getBody();
		if ($this->provider == 'RFS') {
			if ($prefix1=='') die('No prefix selected');
			$mobNumber =  get_string_between($page, '>+'.$prefix1, '</a>');
			if ($mobNumber != '') { $this->setMobileNumber($prefix1, $mobNumber); return $this->mobileNum; }
			$mobNumber =  get_string_between($page, '>+'.$prefix2, '</a>');
			if ($mobNumber != '') { $this->setMobileNumber($prefix2, $mobNumber); return $this->mobileNum; }
			$mobNumber =  get_string_between($page, '>+'.$prefix3, '</a>');
			if ($mobNumber != '') { $this->setMobileNumber($prefix3, $mobNumber); return $this->mobileNum; }
			die('Cannot find prefix');
		} elseif ($this->provider == 'RSO') {
			if ($prefix1=='') die('No prefix selected');
			$mobNumber = get_string_between($page, $prefix1, '.php');
			$mobNumber = trim($mobNumber);
			$this->setMobileNumber($prefix1, $mobNumber);
			return $this->mobileNum;
		}
	}


	function waitForSMS($keyword, $tries, $interval) {
		$newSMS = false;
		$loopcount = 0;
		while (($newSMS == false) && ($loopcount < $tries)) {
			$loopcount++;
			$sms = trim(html_entity_decode($this->getLastSMS()));
			if (strpos($sms, $keyword) !== false) {
				$newSMS = true;
				echo 'Got it<br>';
				return $sms;
			}
			else {
				echo 'Last SMS was: ' .trim($sms).' (No match for '.$keyword.')<br>';
				ob_flush();
				flush();
				sleep($interval);
			}
		}
		return $newSMS;
	}

	function getLastSMS() {

		$start_anchor = $this->smsAnchor;
		if ($this->provider == 'RFS')
			$this->HTTP->setURL($this->providerURL2.$this->mobileNum.'.html');
		elseif ($this->provider == 'RSO') 
			$this->HTTP->setURL($this->providerURL2.$this->mobileNum.'.php');
		else die('Critical error');

		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		$this->HTTP->setHeader(array(
			'Referer'    => $this->providerURL2,
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107 Safari/537.36'
		));

		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}

		$page = $response->getBody();
		if ($this->provider == 'RFS') {
			$section = get_string_between($page, 'From Number', '</table');
			//$sms = get_string_between($section, '</td><td>', '</tr>');
			//$sms = get_string_between($sms, 'ago</td><td>', '</td>');
			$sms = get_string_between($section, $start_anchor, '60 minutes');
		} elseif ($this->provider == 'RSO') {
			$section = get_string_between($page, '<table border="1" id="messages">', '</table>');
			$sms = get_string_between($section, 'ago', '</tr>');
			$sms = get_string_between($sms, '</td><td>', '<td>');
			$sms = trim($sms);
		}
		return $sms;
	}

} //class

/* TESTING CODE */
/*****************
$http = new Http_Request2;
$mob = new Mobile($http);

echo 'Getting new number: ';
echo $mob->getNewMobile('47', '44', '9999')."\n";
echo $mob->getLastSMS();
die();
$result = $mob->waitForSMS('Starbucks', 6, 10);
if ($result != false)
	echo $result;
else
	echo 'Waited but SMS didnt came';
*************/
?>
