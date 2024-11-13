<?php
session_start();

// Clear all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Optionally clear any specific cookies you've set
setcookie('remember_me', '', time()-3600, '/');

// Redirect to login page with a success message
header('Location: pages/login.php?logout=success');
exit;
?>