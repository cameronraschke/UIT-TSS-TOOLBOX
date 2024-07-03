<?php
#header.php
session_start();

$login_user = $_SESSION['login_user'];
$_SESSION['url'] = $_SERVER['REQUEST_URI'];

if ($login_user == '') {
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

$rand = rand(1,1000);

$header = "<html lang='en'>

<head>
<meta charset='UTF-8'>
<link rel='stylesheet' type='text/css' href='/css/main.css?$rand' />
<title>Plutomail Web</title>
</head>
<body>


</div>";
?>