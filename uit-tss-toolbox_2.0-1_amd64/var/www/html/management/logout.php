<?php
#logout.php
require('/var/www/html/management/header.php');

	my_session_destroy();
	my_session_regenerate_id();
	//setcookie ('account', "", time() - 3600, "/");
	//setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: /login.php");
?>
