<?php
// includes/functions.php

function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount, $currency = null)
{
    $currency = $currency ?: DEFAULT_CURRENCY;
    return number_format($amount, 2) . ' ' . $currency;
}

function formatDate($date, $format = 'd/m/Y')
{
    if (!$date) return '';
    return date($format, strtotime($date));
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function getStockStatus($quantity, $minLevel)
{
    if ($quantity == 0) {
        return '<span class="badge bg-danger">Out of Stock</span>';
    } elseif ($quantity <= $minLevel) {
        return '<span class="badge bg-warning">Low Stock</span>';
    } else {
        return '<span class="badge bg-success">In Stock</span>';
    }
}

function getExpiryStatus($expiryDate)
{
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $interval = $today->diff($expiry);
    $days = $interval->days;

    if ($expiry < $today) {
        return '<span class="badge bg-danger">Expired</span>';
    } elseif ($days <= 30) {
        return '<span class="badge bg-warning">Expiring Soon</span>';
    } else {
        return '<span class="badge bg-success">Valid</span>';
    }
}

function logActivity($db, $action, $details = null)
{
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
}
// Ensure session is started once
function ensureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getCsrfToken()
{
    ensureSession();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verifyCsrfToken($token)
{
    ensureSession();
    return isset($_SESSION['_csrf_token']) && is_string($token) && hash_equals($_SESSION['_csrf_token'], $token);
}

function requireCsrfToken()
{
    if (!isset($_POST['_csrf']) || !verifyCsrfToken($_POST['_csrf'])) {
        throw new Exception("Invalid request token. Please refresh and try again.");
    }
}
