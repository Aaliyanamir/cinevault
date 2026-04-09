<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$db = getDB();

if ($action === 'save') {
    $movieId = intval($_POST['movie_id'] ?? 0);
    $progress = intval($_POST['progress'] ?? 0);
    
    $stmt = $db->prepare("INSERT INTO watch_history (user_id, movie_id, progress, last_watched) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE progress = ?, last_watched = NOW(), watch_count = watch_count + 1");
    $stmt->bind_param("iiii", $userId, $movieId, $progress, $progress);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get') {
    $movieId = intval($_GET['movie_id'] ?? 0);
    
    $stmt = $db->prepare("SELECT progress FROM watch_history WHERE user_id = ? AND movie_id = ?");
    $stmt->bind_param("ii", $userId, $movieId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode(['success' => true, 'progress' => $result['progress'] ?? 0]);
    exit;
}
?>