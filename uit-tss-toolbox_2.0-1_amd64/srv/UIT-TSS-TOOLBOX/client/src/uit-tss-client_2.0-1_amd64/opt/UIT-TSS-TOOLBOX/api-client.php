#!/bin/php
<?php
require('/opt/UIT-TSS-TOOLBOX/include.php');

$url = 'http://10.0.0.1:1411/api/endpoint.php';

$fd = fopen('php://stdin', 'r');
$input = trim(fgets(STDIN));
fclose($fd);

$arr = explode('|', $input);

$queryType = $arr[0];
$table = $arr[1];
$columns = explode(', ', htmlspecialchars($arr[2]));
$tagNum = $arr[3];
$notnull = explode(', ', htmlspecialchars($arr[4]));


$postData = http_build_query(
  array(
    'password' => 'UHouston!',
    'tagnumber' => $tagNum,
    'table' => $table,
    'queryType' => $queryType,
    'columns' => json_encode($columns),
    'notnull' => json_encode($notnull),
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

foreach (json_decode($result) as $key => $value) {
  echo htmlspecialchars_decode($value);
}
?>