<?php
#login.php
session_start();
include('/var/www/html/management/php/include.php');


if (isset($_POST["username"]) && isset($_POST["password"])) {
    $db = new db();
	$db->select("SELECT name FROM logins WHERE username = '" . $_POST["username"] . "' AND password = '" . $_POST["password"] . "'");
    if (arrFilter($db->get()) === 0 && count($db->get()) === 1) {
        foreach ($db->get() as $key => $value) {
            setcookie ('authorized', 'yes', time() + (10800), "/");
            $_SESSION['login_user'] = $value["name"];
            unset($_POST["username"]);
            unset($_POST["password"]);
            if (isset($_SERVER['REQUEST_URI'])) {
                header("Location: " . $_SERVER['REQUEST_URI'] );
            } else {
                header("Location: index.php");
            }
        }
    } else {
        setcookie ('authorized', 'no', time() - (3600), "/");
        unset($_SESSION['login_user']);
        unset($_POST["username"]);
        unset($_POST["password"]);
        header("Location: login.php");
    }
}
?>

<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" href="/css/main.css" />
        <title>TechComm Laptop Managment</title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    </head>
    <body>
        <div class="login-title" style="text-align:center;">
            <h1 style="margin:auto;">TechComm Laptop Management Login</h1>
        </div>
        <div class="login-form">
            <form method="post" class="styled-form">
                <label for="username">Username</label>
                <input type="text" name="username" autocomplete="username" required autofocus>
                <label for="password">Password</label>
                <input type="password" name="password" autocomplete="current-password" required>
                <input type="submit" value="Login"></input>
            </form>
        </div>
        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>
        <div class="uit-footer">
            <img src="https://uh.edu/infotech/_images/_reorg-images/uh-2ndry-uit-artboard_horiz-reverse_black.svg">
        </div>
    </body>
</html>
