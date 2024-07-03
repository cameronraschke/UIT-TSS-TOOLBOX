<?php
#login.php
session_start();
include('mysql/mysql-functions');

$uuid = md5(rand(100000000,999999999));
unset($_SESSION['uuid']);
$_SESSION['uuid'] = $uuid;

unset($username);
$username = ($_POST['username']);

if (isset($_POST['username'])) {
	#$sql = "INSERT INTO logins (user, date, uuid, ip, hostname, uri) VALUES ('$username', '$time', '".$_SESSION['uuid']."', '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['REMOTE_HOST']."', '".$_SERVER['REQUEST_URI']."')";
	#$results = $conn->query($sql);

	// $sql = "SELECT email FROM users WHERE email = '$username'";
	// $results = $conn->query($sql);
	// while ($row = $results->fetch_assoc()) {
	// 	if ($row['email'] == $username) {
	 		$_SESSION['login_user'] = $_POST['username'];
	 		header("Location: index.php");
	// 	}
	// }
}
?>

<html>
    <style>
        * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-size: 1em;
                content-align: center;
                font-family: sans-serif;
        }

        .logintitle1 {
                display: flex;
                position: relative;
                width: 25%;
                height: auto;
                top: 10%;
                left: 35%;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
                border-bottom: 2px solid #BE77D6;
                margin: 5% 2% 2% 2%;
            padding: 1% 1% 1% 1%;
            text-align: center;
        }


        .loginform1 {
                display: flex;
                position: relative;
                width: 25%;
                height: auto;
                top: 10%;
                left: 35%;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
                border-bottom: 2px solid #BE77D6;
                margin: 2% 2% 2% 2%;
        }

        .styled-form {
                position: relative;
                width: 100%;
                margin: 5px 5px 5px 5px;
                padding: 20px 20px;
        }
        .styled-form input[type=text] {
                padding: 12px 20px;
                margin: 2% 10% 2% 10%;
                width: 80%;
                border: 1px solid gray;
        }
        .styled-form input[type=text]:focus {
                border: 1px solid black;
        }
        .styled-form input[type=submit] {
                padding: 8px 10px;
                width: 25%;
                margin: 8% 65% 5% 35%;
        }
    </style>
    <body>
        <div class="logintitle1">
            <h1>Plutomail Web Login</h1>
        </div>
        <div class="loginform1">
            <form method="post" class="styled-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <label>Username</label>
            <input type='text' name='username' pattern='([a-zA-Z0-9])+(@cougarnet.uh.edu){1}' autocomplete='email' required autofocus>
            <input type='submit' value='Login'></input>
            </form>
        </div>
    </body>
</html>
