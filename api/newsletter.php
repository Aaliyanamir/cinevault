<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

$db = getDB();

// Check if already subscribed
$check = $db->prepare("SELECT id FROM email_subscribers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already subscribed']);
    exit;
}

// Insert new subscriber
$stmt = $db->prepare("INSERT INTO email_subscribers (email, subscribed_at) VALUES (?, NOW())");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Subscribed successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $db->error]);
}
?>