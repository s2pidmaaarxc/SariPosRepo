<?php
//  auth/logout.php — Direct Logout (non-AJAX)
//  Second option logout button :<

require_once '../config.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/index.html');
exit();
