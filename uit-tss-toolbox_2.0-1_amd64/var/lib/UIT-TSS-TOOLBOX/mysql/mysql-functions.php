#!/usr/bin/php
<?php

function select ($sql) {
    require('/var/lib/UIT-TSS-TOOLBOX/PDO-connect');
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $row = $sql->fetchAll(PDO::FETCH_COLUMN);
    foreach ($row as $key => $value) {
        return $value;
    }
}

?>