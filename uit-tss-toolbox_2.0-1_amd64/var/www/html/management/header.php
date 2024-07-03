<?php
#header.php
session_start();

$login_user = $_SESSION['login_user'];
$_SESSION['url'] = $_SERVER['REQUEST_URI'];

if (empty($login_user)) {
	setcookie ('account', "", time() - 3600, "/");
	setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: login.php");
}

if ($_COOKIE['authorized'] == "yes") {
	setcookie ('authorized', 'yes', time() + (600), "/");
} else {
	setcookie ('account', "", time() - 3600, "/");
	setcookie ('authorized', 'no', time() - 3600, "/");
	header("Location: login.php");
}

?>