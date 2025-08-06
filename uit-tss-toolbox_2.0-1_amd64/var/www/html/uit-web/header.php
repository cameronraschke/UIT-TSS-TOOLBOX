<?php
if (isset($_POST)) {
    echo $_POST["username"];
    echo $_POST["password"];
}
    // if ($_COOKIE["authCookie"] != "Yes") {
        // header("Location: /logout.php");
        // exit();
    // }
?>