<?php
#logout.php
	setcookie ('account', "", time() - 3600, "/");
	setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: /login.php");
?>
