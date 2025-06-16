#!/bin/php
<?php
require('/var/www/html/php/include.php');

$url = 'http://10.0.0.1:1411/api/endpoint.php';

$fd = fopen('php://stdin', 'r');
$input = trim(fgets(STDIN));
fclose($fd);

$arr = explode('|', $input);

$queryType = $arr[0];
$table = $arr[1];
$columns = explode(', ', htmlspecialchars($arr[2]));
$tagNum = $arr[3];
$preparedCols = explode(', ', htmlspecialchars($arr[4]));
$notnull = explode(', ', htmlspecialchars($arr[5]));


$postData = http_build_query(
  array(
    'password' => 'UHouston!',
    'tagnumber' => $tagNum,
    'table' => $table,
    'queryType' => $queryType,
    'columns' => json_encode($columns),
    'preparedCols' => json_encode($preparedCols),
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

$jsonArr = json_decode($result, true);

foreach ($jsonArr as $key => $value) {
  if (count($jsonArr) > 1) {
    echo htmlspecialchars_decode($key) . "|" . htmlspecialchars_decode($value) . PHP_EOL;
  } elseif (count($jsonArr) === 1) {
    echo htmlspecialchars_decode($value);
  }
}
?>