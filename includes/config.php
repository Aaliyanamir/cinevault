<?php
// Database Configuration
$envHost = getenv('DB_HOST');
$envUser = getenv('DB_USER');
$envPass = getenv('DB_PASS');
$envName = getenv('DB_NAME');

define('DB_HOST', $envHost ?: 'localhost');
define('DB_USER', $envUser ?: 'root');
define('DB_PASS', $envPass ?: '');
define('DB_NAME', $envName ?: 'moviesdb');

// Site Configuration
$siteName = getenv('SITE_NAME');
$siteUrl = getenv('SITE_URL');
$siteEmail = getenv('SITE_EMAIL');

define('SITE_NAME', $siteName ?: 'CineVault');
define('SITE_URL', $siteUrl ?: 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME'] ?: '/'));
define('SITE_EMAIL', $siteEmail ?: 'admin@cinevault.com');

// TMDB API (Optional - for bulk import)
define('TMDB_API_KEY', ''); // Get from themoviedb.org
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                error_log('Database connection failed: ' . $conn->connect_error);
                return null;
            }
            $conn->set_charset('utf8mb4');
        } catch (Throwable $e) {
            error_log('Database connection exception: ' . $e->getMessage());
            return null;
        }
    }
    return $conn;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function getUser($userId = null) {
    $db = getDB();
    if (!$db) return null;
    $id = $userId ?? ($_SESSION['user_id'] ?? 0);
    if (!$id) return null;
    
    $stmt = $db->prepare("SELECT id, username, email, avatar, role, theme, email_notifications FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateMovieRating($movieId) {
    $db = getDB();
    if (!$db) return 0;
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM user_ratings WHERE movie_id = ? AND is_approved = 1");
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $newRating = round($result['avg_rating'] ?? 0, 1);
    
    $update = $db->prepare("UPDATE movies SET rating = ? WHERE id = ?");
    $update->bind_param("di", $newRating, $movieId);
    $update->execute();
    return $newRating;
}

function getContinueWatching($userId, $limit = 10) {
    $db = getDB();
    if (!$db) return [];
    $stmt = $db->prepare("
        SELECT m.*, wh.progress, wh.last_watched 
        FROM watch_history wh
        JOIN movies m ON wh.movie_id = m.id
        WHERE wh.user_id = ?
        ORDER BY wh.last_watched DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addRecentlyViewed($userId, $movieId) {
    $db = getDB();
    if (!$db) return;
    $now = date('Y-m-d H:i:s');
    
    $check = $db->prepare("SELECT id FROM recently_viewed WHERE user_id = ? AND movie_id = ?");
    $check->bind_param("ii", $userId, $movieId);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $update = $db->prepare("UPDATE recently_viewed SET viewed_at = ? WHERE user_id = ? AND movie_id = ?");
        $update->bind_param("sii", $now, $userId, $movieId);
        $update->execute();
    } else {
        $insert = $db->prepare("INSERT INTO recently_viewed (user_id, movie_id, viewed_at) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $userId, $movieId, $now);
        $insert->execute();
    }
}

function getSetting($key, $default = '') {
    $db = getDB();
    if (!$db) return $default;
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['setting_value'] ?? $default;
}
?>