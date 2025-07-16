<?php
require("/var/www/html/uit-web/php/include.php");

if (!isset($_GET["type"])) {
  exit();
}

// if ($_GET["password"] !== md5("WEB_SVC_PASSWD")) {
//   exit();
// }

$db = new db();

if (isset($_GET["tagnumber"])) {
  $db->Pselect("SELECT tagnumber FROM locations WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
  if (strFilter($db->get()) === 1) {
    exit();
  }
}

header("X-Accel-Buffering: no");
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");


if ($_GET["type"] == "server_time") {
  $event = "server_time";
  $data = json_encode(array('server_time' => date('r')));
}


if ($_GET["type"] == "job_queue") { 
  $db->Pselect("SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, remote.status AS 'remote_status', DATE_FORMAT(remote.present, '%m/%d/%y, %r') AS 'remote_time_formatted' FROM remote LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
  foreach ($db->get() as $key => $value) {
    $event = "server_time";
    $data = json_encode($value);
  }
  unset($value);
}

if ($_GET["type"] == "live_image" && isset($_GET["tagnumber"])) {
  $db->Pselect("SELECT time, screenshot FROM live_images WHERE tagnumber = :tagnumber", array(':tagnumber' => $_GET["tagnumber"])) {
  foreach ($db->get() as $key => $value) {
    $event = "live_image";
    $data = json_encode($value);
  }
  unset($value);
}


echo "event: " . $event . "\n";
echo "data: " . $data;
echo "\n\n";
if (ob_get_contents()) {
  ob_end_flush();
}
flush();
?>