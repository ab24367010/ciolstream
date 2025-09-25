<?php
// user/ajax/update_progress.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$video_id = isset($input['video_id']) ? (int)$input['video_id'] : 0;
$progress_seconds = isset($input['progress']) ? (int)$input['progress'] : 0;
$total_duration = isset($input['duration']) ? (int)$input['duration'] : 0;
$completed = isset($input['completed']) ? (bool)$input['completed'] : false;

if (!$video_id || $progress_seconds < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Update view count
try {
    $stmt = $pdo->prepare("UPDATE videos SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$video_id]);
} catch (PDOException $e) {
    error_log("Error updating view count: " . $e->getMessage());
}

if (updateVideoProgress($pdo, $user_id, $video_id, $progress_seconds, $total_duration, $completed)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update progress']);
}
?>