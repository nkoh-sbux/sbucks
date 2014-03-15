<?php

if ($_POST["btn"] == "Save") {
	echo 'Saved.';
	file_put_contents('cardlist.txt', $_POST["list"]);
}
$data = file_get_contents('cardlist.txt');

?>
<html>
<body>
<form method="post">
<textarea name="list" cols="80" rows="20"><?php echo $data; ?></textarea>
<input type="submit" name="btn" value="Save">
</form>
</body>
</html>
