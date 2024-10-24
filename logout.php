<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
if (session_destroy()) {
    $response = ['success' => true];
} else {
    $response = ['success' => false, 'message' => 'Failed to destroy session'];
}

// Return a JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>