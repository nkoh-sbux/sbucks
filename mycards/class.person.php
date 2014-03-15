<?php
require_once 'HTTP/Request2.php';
require_once 'functions.php';

class Person {
	public $name	= '';
	public $address	= '';
	public $country	= '';
	public $state	= '';
	public $postalcode	= '';

	private $providerURL	= 'http://www.fakenamegenerator.com/';
	private $HTTP		= null;

	function __construct($obj_HTTP) {
		$this->HTTP = $obj_HTTP;
	}
	

	function getNewPerson() {
		$this->HTTP->setURL($this->providerURL);
		$this->HTTP->setMethod(HTTP_Request2::METHOD_GET);
		try {
			$response = $this->HTTP->send();
		} catch (Exception $exc) {
			process_HTTP_exception($exc, true);
		}
		$page = $response->getBody();
		$content = get_string_between($page, '<div class="info">', '</html>');
		$this->name = get_string_between($content, '<h3>', '</h3>');
		$this->address = get_string_between($content, '<div class="adr">', '<br/>');
		$this->state = get_string_between($content, '<br/>', ',');
		$this->postalcode = 75000+rand(100,500);
		return true;
	}

}

/******* TEST CODE 
$http = new HTTP_Request2();
$p = new Person($http);
$p->getNewPerson();
echo 'Name: ' . $p->name;
echo 'Address: ' . $p->address;
echo 'Country: ' . $p->country;
echo 'State: ' . $p->state;
echo 'Postal: ' . $p->postalcode;
*************/

?>
