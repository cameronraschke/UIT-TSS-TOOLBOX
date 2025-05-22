<?php
include("/var/www/html/management/php/include.php");

if ($_GET["password"] !== "UHouston!") {
  exit();
}

$url = 'http://localhost:1411/api/endpoint.php';

$postData = http_build_query(
  array(
    'password' => 'UHouston!',
    'tagnumber' => '626366',
    'table' => 'locations',
    'columns' => json_encode(array('tagnumber', 'system_serial', 'location')),
    'location' => 'Cams Desk'
  )
);

$opts = array('http' =>
    array(
      'method' => 'POST',
      'header' => "Content-type: application/x-www-form-urlencoded",
      'content' => $postData
    )
);

$context = stream_context_create($opts);

$result = file_get_contents($url, false, $context);

echo $result;


?>