<?php
// ============================================================
// SoilSync — db.php
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'soilsync');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fff0f0;border-left:4px solid #c00;margin:40px">
        <h2>⚠️ Database Connection Failed</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Make sure XAMPP MySQL is running and run <strong>soilsync_phase1.sql</strong> in phpMyAdmin.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: auth.php'); exit; }
}

function currentUser($conn): ?array {
    if (!isLoggedIn()) return null;
    $id = (int)$_SESSION['user_id'];
    $r  = $conn->query("SELECT u.*, l.division, l.district FROM users u LEFT JOIN locations l ON u.location_id=l.id WHERE u.id=$id LIMIT 1");
    return $r ? $r->fetch_assoc() : null;
}

function clean($conn, string $s): string {
    return $conn->real_escape_string(trim($s));
}

function unreadCount($conn, int $uid): int {
    $r = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0");
    return $r ? (int)$r->fetch_assoc()['c'] : 0;
}
?>
