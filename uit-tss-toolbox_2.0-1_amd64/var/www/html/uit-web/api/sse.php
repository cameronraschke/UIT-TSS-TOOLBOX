<?php
require("/var/www/html/uit-web/php/include.php");

if (!isset($_GET["type"])) {
  exit();
}

// if ($_GET["password"] !== md5("WEB_SVC_PASSWD")) {
//   exit();
// }

$db = new db();

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");


if ($_GET["type"] == "server_time") {
    $event = "server_time";
    $data = json_encode(array('server-time' => date('r')));
}


echo "event: " . $event . "\n";
echo "data: " . $data . "\n\n";
flush();
?>