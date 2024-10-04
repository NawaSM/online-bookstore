<?php
session_start();
require_once '../includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo('admin_dashboard.php');
    } else {
        redirectTo('user_dashboard.php');
    }
} else {
    redirectTo('login.php');
}