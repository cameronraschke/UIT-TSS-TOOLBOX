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
    <p><span style='float: left;'><a href='/index.php'>Return Home</a></span></p>
    <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
    <br>
    <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
  </div>

  <div class='pagetitle'><h1>Supported Emojis</h1></div>
  <div class='pagetitle'><h2>Type the left column's characters in order to get the corresponding emoji.</h2></div>

    <div class='row'>
      <div class='column'>
        <div class='styled-table' style='margin: 4% 1% 1% 7%;'>
          <table>
            <thead>
              <tr>
                <th>Keyword</th>
                <th>Replacement</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $db->select("SELECT IF (NOT SUBSTR(keyword, 1, 1) IN (':', ';'), CONCAT(':', keyword), keyword) AS 'keyword_formatted', replacement FROM static_emojis ORDER BY keyword ASC");
            foreach($db->get() as $key => $value) {
              echo "<tr><td>" . htmlspecialchars($value["keyword_formatted"]) . "</td><td>" . htmlspecialchars($value["replacement"]) . "</td></tr>";
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>


    <div class="uit-footer">
      <img src="/images/uh-footer.svg">
    </div>
  </body>
</html>