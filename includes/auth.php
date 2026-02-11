<?php
// auth.php
// Authentication & session helper functions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /courier-management/index.php");
        exit();
    }
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isAgent() {
    return isLoggedIn() && $_SESSION['role'] === 'agent';
}

function isUser() {
    return isLoggedIn() && $_SESSION['role'] === 'user';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        echo "Access denied. Admin only.";
        exit();
    }
}

function requireAgent() {
    requireLogin();
    if (!isAgent()) {
        echo "Access denied. Agent only.";
        exit();
    }
}

function requireUser() {
    requireLogin();
    if (!isUser()) {
        echo "Access denied. User only.";
        exit();
    }
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: /courier-management/index.php");
    exit();
}
?>
