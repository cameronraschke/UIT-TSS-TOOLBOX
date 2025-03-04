<?php
#login.php
session_start();
require('/var/www/html/management/php/include.php');


if (isset($_POST["username"]) && isset($_POST["password"])) {
    $db = new db();
	$db->Pselect("SELECT name FROM logins WHERE username = BINARY :username AND password = BINARY :password", array(':username' => $_POST["username"], ':password' => $_POST["password"]));
    if (arrFilter($db->get()) === 0 && count($db->get()) === 1) {
        foreach ($db->get() as $key => $value) {
            setcookie ('authorized', 'yes', time() + (10800), "/");
            $_SESSION['login_user'] = $value["name"];
            unset($_POST["username"]);
            unset($_POST["password"]);
            header("Location: /index.php");
        }
    } else {
        setcookie ('authorized', 'no', time() - (3600), "/");
        unset($_SESSION['login_user']);
        unset($_POST["username"]);
        unset($_POST["password"]);
        $err = "Invalid credentials, try again.";
    }
}
?>

<html>
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" type="text/css" href="/css/main.css" />
        <title>Login - UIT Client Mgmt</title>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    </head>
    <body>
        <div class="login-title" style="text-align:center; top: 10%;">
            <h1 style="margin:auto;">TechComm Laptop Management Login</h1>
        </div>
        <div style="top: 10%;" class="login-form">
            <div>
                <form method="post" class="styled-form">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="username" required autofocus>
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="current-password" required>
                    <?php if (isset($err)) { echo "<div><h3 style='color: #C8102E;'>Invalid credentials, try again. </h3></div>"; } ?>
                    <input type="submit" value="Login"></input>
                </form>
            </div>
        </div>
        <script>
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>
        <div style="top: 10%;" class="uit-footer">
            <img src="/images/uh-footer.svg">
        </div>
    </body>
</html>
