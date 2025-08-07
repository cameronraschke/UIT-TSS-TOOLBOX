<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

$jsonData = json_decode(file_get_contents('php://input'), true); 


if (isset($jsonData["username"]) && isset($jsonData["password"])) {
$dbPSQL = new dbPSQL();
$dbPSQL->Pselect("SELECT name FROM logins WHERE username = :username AND password = :password", array(':username' => hash('sha256', $jsonData["username"]), ':password' => hash('sha256', $jsonData["password"])));
if (arrFilter($dbPSQL->get()) === 0 && count($dbPSQL->get()) === 1) {
foreach ($dbPSQL->get() as $key => $value) {
//setcookie ('authorized', 'yes', time() + (10800), "/");
// my_session_regenerate_id();
$_SESSION['login_user'] = $value["name"];
$_SESSION['authorized'] = "yes";
unset($_POST);
header("Location: /index.php");
}
} else {
//setcookie ('authorized', 'no', time() - (3600), "/");
unset($_SESSION['login_user']);
unset($_POST);
$err = "Invalid credentials, try again.";
}
}

if ($_POST) {
  header( "Location: {$_SERVER['REQUEST_URI']}", true, 303 );
  unset($_POST);
}
?>

<!DOCTYPE html>
  <head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="css/main.css?<?php echo filemtime('css/main.css'); ?>" />
    <title>Login - UIT Client Mgmt</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/init.js?<?php echo filemtime('js/init.js'); ?>"></script>
  </head>
  <body>
    <div class='login-form'>
      <div>
        <h1>UIT Web Login</h1>
      </div>
      <div>
        <form method="POST" id="loginForm">
          <div>
            <div><label for="username">Username</label></div>
            <div><input type="text" name="username" id="username" autocapitalize='none' autocomplete='username' autocorrect='off' spellcheck='false' required autofocus></div>
          </div>
          <div>
            <div><label for="password">Password</label></div>
            <div><input type="password" name="password" id="password" autocomplete="current-password" required></div>
          </div>
          <?php if (isset($err)) { echo "<div><h3>Invalid credentials, try again. </h3></div>"; } ?>
          <div style='display: flex;'><input type="submit" value="Login"></input></div>
        </form>
      </div>
    </div>

    <div class="uit-footer">
      <img src="/images/uh-footer.svg">
    </div>
    <script>
      getCreds();
      if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
      }
    </script>
  </body>
</html>