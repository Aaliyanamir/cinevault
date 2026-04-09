<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// ADD COMMENT
// ============================================
if ($action === 'add') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Login required']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $movieId = intval($_POST['movie_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
        exit;
    }
    
    if ($movieId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
        exit;
    }
    
    $db = getDB();
    
    // Auto approve setting check karo (default 0 means pending)
    $autoApprove = getSetting('comments_auto_approve', '0') == '1' ? 1 : 0;
    
    $stmt = $db->prepare("INSERT INTO comments (user_id, movie_id, comment, is_approved, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisi", $userId, $movieId, $comment, $autoApprove);
    
    if ($stmt->execute()) {
        $msg = $autoApprove ? 'Comment posted successfully' : 'Comment awaiting admin approval';
        echo json_encode(['success' => true, 'message' => $msg, 'auto_approved' => $autoApprove]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $db->error]);
    }
    exit;
}

// ============================================
// GET COMMENTS (Only approved ones for frontend)
// ============================================
if ($action === 'get') {
    $movieId = intval($_GET['movie_id'] ?? 0);
    
    if ($movieId <= 0) {
        echo json_encode(['success' => true, 'comments' => []]);
        exit;
    }
    
    $db = getDB();
    
    // Sirf approved comments fetch karo (is_approved = 1)
    $stmt = $db->prepare("
        SELECT c.*, u.username 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.movie_id = ? AND c.is_approved = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>