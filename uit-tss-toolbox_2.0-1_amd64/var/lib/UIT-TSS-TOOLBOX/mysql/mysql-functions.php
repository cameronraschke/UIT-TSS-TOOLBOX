#!/usr/bin/php
<?php

#require('/var/lib/UIT-TSS-TOOLBOX/PDO-connect');

function select ($sql) {
    $attempts = 0;

do {
    try {
        $user = "laptops";
        $pass = "UHouston!";
        $pdo = new PDO('mysql:host=10.0.0.1;dbname=laptopDB', $user, $pass, array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => true,
            PDO::ERRMODE_EXCEPTION => true
        ));
    } catch (PDOException $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        $attempts++;
        sleep(1);
        continue;
    }
    break;
} while ($attempts < 10);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $sql->fetchAll(PDO::FETCH_COLUMN);
    foreach ($row as $key => $value) {
        return $value;
    }
}

?>