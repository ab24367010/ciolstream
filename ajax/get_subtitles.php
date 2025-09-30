<?php
// ajax/get_subtitles.php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$video_id = isset($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
$language = isset($_GET['lang']) ? trim($_GET['lang']) : 'en';

if (!$video_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid video ID']);
    exit;
}

// Check user access
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !userCanAccessSubtitles($user['status'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Get subtitle file
$stmt = $pdo->prepare("SELECT srt_file_path FROM subtitles WHERE video_id = ? AND language = ?");
$stmt->execute([$video_id, $language]);
$subtitle = $stmt->fetch();

if (!$subtitle || !file_exists($subtitle['srt_file_path'])) {
    echo json_encode(['success' => false, 'error' => 'Subtitle not found']);
    exit;
}

$srt_content = file_get_contents($subtitle['srt_file_path']);
$subtitles = parseSrtContent($srt_content);

echo json_encode([
    'success' => true,
    'subtitles' => $subtitles
]);
?>