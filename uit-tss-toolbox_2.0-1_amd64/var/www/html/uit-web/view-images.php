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

if ($_SESSION['authorized'] != "yes") {
  exit();
}

if (isset($_POST["delete-image"]) && $_POST["delete-image"] == "1") {
  $db->deleteImage($_POST["delete-image-uuid"], $_POST["delete-image-time"], $_POST["delete-image-tagnumber"]);
}

if (isset($_POST["rotate-image"]) && $_POST["rotate-image"] == "1") {
  $db->Pselect("SELECT image FROM client_images WHERE uuid = :uuid AND time = :time AND tagnumber = :tagnumber", array(':uuid' => $_POST["rotate-image-uuid"], ':time' => $_POST["rotate-image-time"], ':tagnumber' => $_POST["rotate-image-tagnumber"]));
  foreach ($db->get() as $key => $value) {
    $rotateImageData = base64_decode($value["image"]);
    $rotateImageObject = imagecreatefromstring($rotateImageData);
    $rotatedImage = imagerotate($rotateImageObject, -90, 0);
    ob_start();
    imagejpeg($rotatedImage, NULL, 100);
    $rotateImageEncoded = base64_encode(ob_get_clean());
    imagedestroy($rotateImageObject);
    $db->updateImage("image", $rotateImageEncoded, $_POST["rotate-image-uuid"]);
  }
  unset($value);
}

if (isset($_GET["uuid"]) && $_GET["download"] == "1") {
  $db->Pselect("SELECT uuid, mime_type, image FROM client_images WHERE uuid = :uuid", array(':uuid' => $_GET["uuid"]));
  foreach ($db->get() as $key => $value) {
    if ($value["mime_type"] == "image/jpeg") {
      //echo "<html><head><script src='/js/include.js'></script></head><body><script>openImage('" . $value["image"] . "')</script></body></html>";
      header("Content-type: " . $value["mime_type"]);
      header("Content-Disposition: attachment; filename=" . $value["uuid"] . ".jpeg");
      header("Content-length: " . strlen(base64_decode($value["image"])));
      echo base64_decode($value["image"]);
    } elseif ($value["mime_type"] == "video/mp4") {
      header("Content-type: " . $value["mime_type"]);
      header("Content-Disposition: attachment; filename=" . $value["uuid"] . ".mp4");
      header("Content-length: " . strlen(base64_decode($value["image"])));
      //echo "<html><body><video preload='metadata' style='max-height:100%; max-width:100%;' controls><source type='video/mp4' src='data:video/mp4;base64," . $value["image"] . "' /></video></body></html>";
      echo base64_decode($value["image"]);
    }
  }
  unset($value);
  exit();
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
        $db->Pselect("SELECT uuid, time, tagnumber, filename, filesize, mime_type, image, note, resolution, DATE_FORMAT(time, '%m/%d/%y, %r') AS 'time_formatted' FROM client_images WHERE tagnumber = :tagnumber ORDER BY time DESC", array(':tagnumber' => $_GET["tagnumber"]));
        if (strFilter($db->get()) === 0) {
          foreach ($db->get() as $key => $image) {
            echo "<div class='grid-box'>";
              echo "<div style='display: table; clear: both; width: 100%;'>";
              //Delete image form
              echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: left;'>";
              echo "<form method='post'>";
              echo "<input type='hidden' name='delete-image' value='1'>";
              echo "<input type='hidden' name='delete-image-uuid' value='" . $image["uuid"] . "'>";
              echo "<input type='hidden' name='delete-image-time' value='" . $image["time"] . "'>";
              echo "<input type='hidden' name='delete-image-tagnumber' value='" . $image["tagnumber"] . "'>";
              echo "<div style='position: relative; top: 0; left: 0;'>";
              echo "[<input type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; color: #C8102E; border: none; margin: 0; padding: 0; cursor: pointer; font-weight: bold;' onclick='this.form.submit()' value='delete'></input>]</form></div></div>";

              
              //Rotate image form
              if (preg_match('/^image\/.*/', $image["mime_type"]) === 1) {
                echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: right;'>";
                echo "<div style='margin: 0 0 1em 0;'><a style='color: black;' href='/view-images.php?download=1&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "&uuid=" . $image["uuid"] . "' target='_blank'><img class='new-tab-image' src='/images/new-tab.svg'></img><b>[download uncompressed image]</b></a></div>";
                echo "<form method='post'>";
                echo "<input type='hidden' name='rotate-image' value='1'>";
                echo "<input type='hidden' name='rotate-image-uuid' value='" . $image["uuid"] . "'>";
                echo "<input type='hidden' name='rotate-image-time' value='" . $image["time"] . "'>";
                echo "<input type='hidden' name='rotate-image-tagnumber' value='" . $image["tagnumber"] . "'>";
                echo "<div style='position: relative; top: 0; right: 0;'>";
                echo "[<input type=submit style='font-size: 1em; background-color: transparent; text-decoration: underline; color: black; border: none; margin: 0; padding: 0; cursor: pointer; font-weight: bold;' onclick='this.form.submit()' value='rotate image 90&deg;'></input>]</form></div></div>";
              }
              if (preg_match('/^video\/.*/', $image["mime_type"]) === 1) {
                echo "<div style='margin: 0 0 1em 0; padding: 0; width: fit-content; float: right;'>";
                echo "<div style='position: relative; top: 0; right: 0;'>";
                echo "<a style='color: black;' href='/view-images.php?download=1&tagnumber=" . htmlspecialchars($_GET["tagnumber"]) . "&uuid=" . $image["uuid"] . "' target='_blank'><img class='new-tab-image' src='/images/new-tab.svg'></img><b>[download uncompressed video]</b></a></div></div>";
              }
              
              echo "</div>";

            echo "<div><p>Upload Timestamp: " . htmlspecialchars($image["time_formatted"]) . "</p>";
            echo "<p>File Info: \"" . htmlspecialchars($image["filename"]) . "\" (" . htmlspecialchars($image["resolution"]) . ", " . htmlspecialchars($image["filesize"]) . " MB" . ")</p>";
            if (strFilter($image["note"]) === 0) {
                echo "<p><b>Note: </b> " . htmlspecialchars($image["note"]) . "</p>";
            }

            echo "<div style='padding: 1em 1px 1px 1px;'>";
            if (preg_match('/^image\/.*/', $image["mime_type"]) === 1) {
              echo "<img style='max-height:100%; max-width:100%; cursor: pointer;' onclick=\"openImage('" . $image["image"] . "')\" src='data:image/jpeg;base64," . $image["image"] . "'></img>";
            } elseif (preg_match('/^video\/.*/', $image["mime_type"]) === 1) {
              echo "<video preload='metadata' style='max-height:100%; max-width:100%;' controls><source type='video/mp4' src='data:video/mp4;base64," . $image["image"] . "' /></video>";
            }
            echo "</div>";   
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