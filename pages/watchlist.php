<?php
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Login required', 'login_required' => true], 401);
}

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$movieId  = intval($_POST['movie_id'] ?? $_GET['movie_id'] ?? 0);
$userId   = $_SESSION['user_id'];
$db       = getDB();

switch ($action) {
    case 'add':
        if (!$movieId) jsonResponse(['error' => 'Invalid movie'], 400);
        $stmt = $db->prepare("INSERT IGNORE INTO watchlist (user_id, movie_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $movieId);
        $stmt->execute();
        jsonResponse(['success' => true, 'message' => 'Added to watchlist']);
        break;

    case 'remove':
        if (!$movieId) jsonResponse(['error' => 'Invalid movie'], 400);
        $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->bind_param("ii", $userId, $movieId);
        $stmt->execute();
        jsonResponse(['success' => true, 'message' => 'Removed from watchlist']);
        break;

    case 'check':
        if (!$movieId) jsonResponse(['in_watchlist' => false]);
        $stmt = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->bind_param("ii", $userId, $movieId);
        $stmt->execute();
        $result = $stmt->get_result();
        jsonResponse(['in_watchlist' => $result->num_rows > 0]);
        break;

    case 'list':
        $stmt = $db->prepare("
            SELECT m.id, m.title, m.slug, m.poster, m.year, m.rating, m.quality, m.duration,
                   w.added_at
            FROM watchlist w
            JOIN movies m ON w.movie_id = m.id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        jsonResponse(['movies' => $movies]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
?>
