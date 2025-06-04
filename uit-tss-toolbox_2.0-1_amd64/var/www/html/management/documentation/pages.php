<?php
require('/var/www/html/management/header.php');
require('/var/www/html/management/php/include.php');

session_start();
if ($_SESSION['authorized'] != "yes") {
  die();
}

$db = new db();
?>

<html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title>Locations - UIT Client Mgmt</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
  </head>
  <body>
    <div class='menubar'>
      <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
      <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>.</span></p>
      <br>
      <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "UTF-8", FALSE); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
    </div>

    <div class='pagetitle'><h1>Documentation - <?php echo htmlspecialchars($_GET["page"]); ?></h1></div>
  </body>
</html>