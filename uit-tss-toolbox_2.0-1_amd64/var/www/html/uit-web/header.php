<?php
header("Access-Control-Allow-Origin: *");
function my_session_start() {
	if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (isset($_SESSION['destroyed'])) {
       if ($_SESSION['destroyed'] < time()-300) {
           // Should not happen usually. This could be attack or due to unstable network.
           // Remove all authentication status of this users session.
           remove_all_authentication_flag_from_active_sessions($_SESSION['login_user']);
           //throw(new DestroyedSessionAccessException);
       }
       if (isset($_SESSION['new_session_id'])) {
           // Not fully expired yet. Could be lost cookie by unstable network.
           // Try again to set proper session ID cookie.
           // NOTE: Do not try to set session ID again if you would like to remove
           // authentication flag.
           session_commit();
           session_id($_SESSION['new_session_id']);
           // New session ID should exist
           session_start();
           return;
       }
   }
}


// My session regenerate id function
function my_session_regenerate_id() {
    // New session ID is required to set proper session ID
    // when session ID is not set due to unstable network.
    $new_session_id = session_create_id();
    $_SESSION['new_session_id'] = $new_session_id;
    
    // Set destroy timestamp
    $_SESSION['destroyed'] = time();
    
    // Write and close current session;
    session_commit();

    // Start session with new session ID
    ini_set('session.use_strict_mode', 0);
    session_id($new_session_id);
    session_start();
    
    // New session does not need them
    unset($_SESSION['destroyed']);
    unset($_SESSION['new_session_id']);
}

function my_session_destroy() {
	if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }
	$_SESSION = array();
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}

	session_unset();
	session_destroy();
}

// Make sure use_strict_mode is enabled.
// use_strict_mode is mandatory for security reasons.
ini_set('session.use_strict_mode', 1);
my_session_start();

// Session ID must be regenerated when
//  - User logged in
//  - User logged out
//  - Certain period has passed
//my_session_regenerate_id();


if (!empty($_SESSION['login_user'])) {
	//setcookie ('authorized', 'no', time() - 3600, "/");
	//&& $_COOKIE['authorized'] == "yes"
	//&& $_SESSION['authorized'] == "yes"
	//setcookie ('authorized', 'yes', time() + (10800), "/");
	$login_user = $_SESSION['login_user'];
	$_SESSION['authorized'] = "yes";
} else {
	my_session_regenerate_id();
	if ($_SERVER['REQUEST_URI'] != "/login.php") {
		header("Location: /login.php");
	}
}

?>