<?php
// ============================================================
//  includes/auth.php — Gestione autenticazione admin
// ============================================================

require_once dirname(__DIR__) . '/config.php';

function requireLogin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        redirect(BASE_URL . '/login.php');
    }
}

function isLoggedIn(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($_SESSION['admin_logged_in']);
}

function adminLogin(string $password): bool {
    $stmt = db()->query('SELECT password_hash FROM admin WHERE id = 1 LIMIT 1');
    $row  = $stmt->fetch();
    if (!$row) return false;
    if (!password_verify($password, $row['password_hash'])) return false;
    // Rigenera session ID per prevenire session fixation
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    return true;
}

function adminLogout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION = [];
    session_destroy();
}
