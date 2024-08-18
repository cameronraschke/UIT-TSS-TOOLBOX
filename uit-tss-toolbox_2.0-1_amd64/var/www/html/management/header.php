<?php
#header.php
session_start();


$login_user = $_SESSION['login_user'];

if (empty($login_user)) {
	setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: /login.php");
}

if ($_COOKIE['authorized'] == "yes") {
	setcookie ('authorized', 'yes', time() + (10800), "/");
} else {
	setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: /login.php");
}

?>