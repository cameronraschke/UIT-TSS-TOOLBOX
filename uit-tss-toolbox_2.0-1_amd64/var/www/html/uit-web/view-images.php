<?php
require('/var/www/html/uit-web/header.php');
require('/var/www/html/uit-web/php/include.php');

$db = new db();

if (strFilter($_GET["tagnumber"]) === 1) {
  http_response_code(500);
  exit();
}

if (preg_match('/^[0-9]{6}$/', $_GET["tagnumber"]) !== 1) {
  http_response_code(500);
  exit();
}

session_start();
if ($_SESSION['authorized'] != "yes") {
  exit();
}

if (isset($_POST["delete-image"]) && $_POST["delete-image"] == "1") {
  $db->deleteImage($_POST["delete-image-uuid"], $_POST["delete-image-time"], $_POST["delete-image-tagnumber"]);
}

?>

<html>
  <head>
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='/css/main.css' />
    <title><?php echo "Images - " . htmlspecialchars($_GET['tagnumber']) . " - UIT Client Mgmt"; ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script src="/js/include.js"></script>
  </head>
  <body>

  <div class='menubar'>
  <p><span style='float: left;'><a href='index.php'>Return Home</a></span></p>
  <p><span style='float: right;'>Logged in as <b><?php echo htmlspecialchars($login_user); ?></b>.</span></p>
  <br>
  <p><span style='float: right;'>Not <b><?php echo htmlspecialchars($login_user); ?></b>? <a href='logout.php'>Click Here to Logout</a></span></p>
  </div>

  <div class='pagetitle'><h1>Images for <?php echo htmlspecialchars($_GET['tagnumber']); ?></h1></div>


<div class='grid-container' style='width: 100%;'>
        <?php
        $db->Pselect("SELECT uuid, time, tagnumber, filename, filesize, image, note, resolution, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted' FROM client_images WHERE tagnumber = :tagnumber ORDER BY time DESC", array(':tagnumber' => $_GET["tagnumber"]));
        if (strFilter($db->get()) === 0) {
          foreach ($db->get() as $key => $image) {
            echo "<div class='grid-box'>";
            echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content;'>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='delete-image' value='1'>";
            echo "<input type='hidden' name='delete-image-uuid' value='" . $image["uuid"] . "'>";
            echo "<input type='hidden' name='delete-image-time' value='" . $image["time"] . "'>";
            echo "<input type='hidden' name='delete-image-tagnumber' value='" . $image["tagnumber"] . "'>";
            echo "<div style='position: relative; top: 0; left: 0;'>";
            echo "<b>[<input type=submit style='background-color: transparent; text-decoration: underline; color:red; border: none; margin: 0; padding: 0; cursor: pointer; font-weight: bold;' onclick='this.form.submit()' value='x'>]</b></input></form></div></div>";
            echo "<div><p>Upload Timestamp: " . htmlspecialchars($image["time_formatted"]) . "</p>";
            echo "<p>File Info: \"" . htmlspecialchars($image["filename"]) . "\" (" . htmlspecialchars($image["resolution"]) . ", " . $image["filesize"] . " MB" . ")</p>";
            if (strFilter($image["note"]) === 0) {
                echo "<p><b>Note: </b> " . htmlspecialchars($image["note"]) . "</p>";
            }

            echo "<img style='max-height:100%; max-width:100%; cursor: pointer;' onclick=\"openImage('" . $image["image"] . "')\" src='data:image/jpeg;base64," . ($image["image"]) . "'></img>";
            echo "</div>";
            echo "</div>";
          }
          unset($image);
          echo "</div>";
        }
        ?>
</div>

<div class="uit-footer">
<img src="/images/uh-footer.svg">
</div>

<script>
if ( window.history.replaceState ) {
window.history.replaceState( null, null, window.location.href );
}
</script>
</body>
</html>