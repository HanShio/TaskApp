<?php
// Page guard: send visitors who are not logged in to the login page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Stop the browser caching private pages
header("Cache-Control: no-store, no-cache, must-revalidate");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}