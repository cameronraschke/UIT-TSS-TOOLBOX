<?php
#login.php
session_start();
include('mysql/mysql-functions');

// $uuid = md5(rand(100000000,999999999));
// unset($_SESSION['uuid']);
// $_SESSION['uuid'] = $uuid;

unset($username);
$username = ($_POST['username']);

if (isset($_POST['username']) && isset($_POST['password'])) {
	dbSelectVal("SELECT username FROM logins WHERE username = '" . $_POST['username'] . "' AND password = '" . $_POST['password'] . "'");
    if (filter($result) == 0) {
        setcookie ('authorized', 'yes', time() + (10800), "/");
        $_SESSION['login_user'] = $_POST['username'];
        unset($_POST['username']);
        unset($_POST['password']);
        header("Location: index.php");
    } else {
        setcookie ('authorized', 'no', time() - (3600), "/");
        unset($_SESSION['login_user']);
        unset($_POST['username']);
        unset($_POST['password']);
        header("Location: login.php");
    }
}
?>

<html>
    <head>
        <meta charset='UTF-8'>
        <link rel='stylesheet' type='text/css' href='/css/main.css' />
        <title>UIT-TSS-Managment Site</title>
    </head>
    <body>
        <div class="login-title">
            <h1>UIT-TSS-TOOLBOX Web Login</h1>
        </div>
        <div class="login-form">
            <form method="post" class="styled-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <label>Username</label>
                <input type='text' name='username' autocomplete='email' required autofocus>
                <input type="password" name="password" required>
                <input type='submit' value='Login'></input>
            </form>
        </div>
    </body>
</html>
