<?php
require("/var/www/html/uit-web/php/include.php");

if (!isset($_GET["tagnumber"])) {
  exit();
}

if ($_GET["password"] !== "WEB_SVC_PASSWD") {
  exit();
}

$db = new db();

$db->Pselect("SELECT tagnumber FROM locations WHERE tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
if (strFilter($db->get()) === 1) {
  exit();
}

$db->Pselect("SELECT remote.present_bool, remote.kernel_updated, client_health.bios_updated, remote.status AS 'remote_status', DATE_FORMAT(remote.present, '%m/%d/%y, %r') AS 'remote_time_formatted' FROM remote LEFT JOIN client_health ON remote.tagnumber = client_health.tagnumber WHERE remote.tagnumber = :tagnumber", array(':tagnumber' => htmlspecialchars($_GET["tagnumber"])));
foreach ($db->get() as $key => $value) {
  $data = array('present_bool' => $value["present_bool"], 'kernel_updated' => $value["kernel_updated"], 'bios_updated' => $value["bios_updated"], 'remote_status' => $value["remote_status"], 'remote_time_formatted' => $value["remote_time_formatted"]);
}
unset($value);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
?>