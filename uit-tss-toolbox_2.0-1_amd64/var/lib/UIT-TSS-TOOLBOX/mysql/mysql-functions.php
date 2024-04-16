#!/usr/bin/php
<?php

require('/var/lib/UIT-TSS-TOOLBOX/PDO-connect');

function DBSelect ($sql) {
    $sql = $pdo->prepare($sql);
    $sql->execute();
    
    $row = $sql->fetchAll(PDO::FETCH_COLUMN);
    foreach ($row as $key => $value) {
        return $value;
    }
}

?>