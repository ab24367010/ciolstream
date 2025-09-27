<?php
// user/ajax/remove_watchlist.php - Fixed to support both videos and series
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
$video_id = isset($input['video_id']) ? (int)$input['video_id'] : null;
$series_id = isset($input['series_id']) ? (int)$input['series_id'] : null;

if (!$video_id && !$series_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid video or series ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

if (removeFromWatchlist($pdo, $user_id, $video_id, $series_id)) {
    logUserActivity($pdo, $user_id, 'watchlist_remove', $video_id);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to remove from watchlist']);
}
?>