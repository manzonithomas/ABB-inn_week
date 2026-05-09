<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    redirect(BASE_URL . '/admin/dashboard.php');
} else {
    redirect(BASE_URL . '/login.php');
}
