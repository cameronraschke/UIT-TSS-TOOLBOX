<?php
require('/var/www/html/management/header.php');
include('/var/www/html/management/php/include.php');

if ($_SESSION['authorized'] != "yes") {
  die();
}

$db = new db();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset='UTF-8'>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title>Support Articles - Emojis</title>
  </head>
  <body>
    <div class='menubar'>
    <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
    <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
    <br>
    <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
  </div>

  <div class='pagetitle' style="text-align:center;"><h1 style="margin:auto;">TechComm Laptop Management Site</h1></div>
  <div class='pagetitle'><h2>Welcome, <?php echo $login_user; ?>.</h2></div>

  </body>
</html>