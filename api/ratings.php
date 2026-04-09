<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// SUBMIT RATING
// ============================================
if ($action === 'submit') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Login required']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $movieId = intval($_POST['movie_id'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    
    if ($rating < 0.5 || $rating > 10) {
        echo json_encode(['success' => false, 'error' => 'Invalid rating (0.5 to 10)']);
        exit;
    }
    
    if ($movieId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid movie ID']);
        exit;
    }
    
    $db = getDB();
    
    // Auto approve setting
    $autoApprove = getSetting('ratings_auto_approve', '0') == '1' ? 1 : 0;
    
    // Check if already rated
    $check = $db->prepare("SELECT id FROM user_ratings WHERE user_id = ? AND movie_id = ?");
    $check->bind_param("ii", $userId, $movieId);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing rating
        $stmt = $db->prepare("UPDATE user_ratings SET rating = ?, review = ?, is_approved = ? WHERE user_id = ? AND movie_id = ?");
        $stmt->bind_param("dsiii", $rating, $review, $autoApprove, $userId, $movieId);
    } else {
        // Insert new rating
        $stmt = $db->prepare("INSERT INTO user_ratings (user_id, movie_id, rating, review, is_approved) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidsi", $userId, $movieId, $rating, $review, $autoApprove);
    }
    
    if ($stmt->execute()) {
        // Update movie average rating (sirf approved ratings ka average)
        $avgStmt = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM user_ratings WHERE movie_id = ? AND is_approved = 1");
        $avgStmt->bind_param("i", $movieId);
        $avgStmt->execute();
        $avgResult = $avgStmt->get_result()->fetch_assoc();
        $newAvg = round($avgResult['avg'] ?? 0, 1);
        
        $updateMovie = $db->prepare("UPDATE movies SET rating = ? WHERE id = ?");
        $updateMovie->bind_param("di", $newAvg, $movieId);
        $updateMovie->execute();
        
        $msg = $autoApprove ? 'Rating submitted successfully' : 'Rating awaiting admin approval';
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $db->error]);
    }
    exit;
}

// ============================================
// GET RATING (Only approved ratings average)
// ============================================
if ($action === 'get') {
    $movieId = intval($_GET['movie_id'] ?? 0);
    
    if ($movieId <= 0) {
        echo json_encode(['success' => true, 'average' => 0, 'count' => 0]);
        exit;
    }
    
    $db = getDB();
    
    // Sirf approved ratings ka average
    $avgStmt = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM user_ratings WHERE movie_id = ? AND is_approved = 1");
    $avgStmt->bind_param("i", $movieId);
    $avgStmt->execute();
    $stats = $avgStmt->get_result()->fetch_assoc();
    
    $response = [
        'success' => true,
        'average' => round($stats['avg'] ?? 0, 1),
        'count' => $stats['count'] ?? 0
    ];
    
    // Get user's rating if logged in (sirf approved ya unapproved dono dikhao user ko)
    if (isset($_SESSION['user_id'])) {
        $userStmt = $db->prepare("SELECT rating, review, is_approved FROM user_ratings WHERE user_id = ? AND movie_id = ?");
        $userStmt->bind_param("ii", $_SESSION['user_id'], $movieId);
        $userStmt->execute();
        $userRating = $userStmt->get_result()->fetch_assoc();
        if ($userRating) {
            $response['user_rating'] = $userRating;
        }
    }
    
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>