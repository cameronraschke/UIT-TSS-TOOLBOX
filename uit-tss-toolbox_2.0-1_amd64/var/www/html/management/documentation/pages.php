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
    <div class='documentation'>
      <?php
        if ($_GET["page"] == "locations") {
          echo "<h3>Update Client Information</h3>";
          echo "<p>
            The page <a href='/locations.php'>locations.php</a> allows you to update multiple aspects of a client. 
            On the left side of the webpage, you will see a box to enter a tag number. 
            Once you enter the tag number, you will be able to modify information about the client. 
            For new clients that have never been entered into the database, there will be no information filled out. This means that you must at least fill out the tag number, serial number, and location. 
            Additionally, for existing clients, the pre-filled information provided will be based off of the most recent information the database has on the client.
            </p>
            <p>
            For checkouts, the form will pre-fill the last known customer name and checkout date if the client has not been returned. 
            A returned client is one that has a <i>Return Date</i> that is earlier or equal to the current date.
            </p>";
          echo "</br>";
          echo "<h3>Advanced Filtering</h3>";
          echo "<p>
            The filtering form on the right side of the page allows you to creat advanced searches that look through every client in the database. 
            You can select multiple values to create advanced filters and the form will tell you how many clients your filter returned. 
            For a given filter in a dropdown menu, then number in parentheses denotes how many clients have that aspect. For example, look at the following:
          </p>";
          echo "<div class='location-form'><select><option>Tech Commons (82)</option><option>SHRL (33)</option></select></div>";
          echo "<p>
            This dropdown menu would denote that 82 clients are in the Tech Commons department and 33 clients are in SHRL.
          </p>";
        }
      ?>
    </div>
    <div class="uit-footer">
      <img src="/images/uh-footer.svg">
    </div>
  </body>
</html>