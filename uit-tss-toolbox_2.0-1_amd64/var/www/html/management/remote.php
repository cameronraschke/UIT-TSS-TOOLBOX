<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
<?php
require('./uit-sql-refresh-server');
require('./mysql-functions');
dbSelect("SELECT date FROM serverstats");
foreach ($arr as $key => $value) {
    echo "<p>$value</p>";
}
?>