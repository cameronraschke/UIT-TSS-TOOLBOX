<h1>Remote Table</h1>
<h3>The remote table monitors laptops currently plugged into the UIT-TSS-TOOLBOX server.</h3>
<?php
require('./uit-sql-refresh-remote');
include('./mysql-functions');
dbSelect("SELECT tagnumber FROM remote");
foreach ($arr as $key => $value) {
    echo "<p>$value</p>";
}
?>