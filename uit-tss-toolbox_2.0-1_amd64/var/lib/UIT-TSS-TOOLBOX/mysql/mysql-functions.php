#!/usr/bin/php
<?php
include('/var/lib/UIT-TSS-TOOLBOX/PDO-connect');

function select ($sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $sql->fetchAll(PDO::FETCH_COLUMN);
    foreach ($row as $key => $value) {
        return $value;
    }
}

?>