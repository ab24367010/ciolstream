<?php
// admin/download_subtitle.php - Download subtitle files
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$video_id = isset($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
$language = isset($_GET['lang']) ? trim($_GET['lang']) : 'en';

if (!$video_id) {
    header('Location: dashboard.php');
    exit;
}

// Get subtitle file path
$stmt = $pdo->prepare("SELECT s.srt_file_path, v.title FROM subtitles s JOIN videos v ON s.video_id = v.id WHERE s.video_id = ? AND s.language = ?");
$stmt->execute([$video_id, $language]);
$result = $stmt->fetch();

if (!$result || !file_exists($result['srt_file_path'])) {
    header('Location: dashboard.php?error=file_not_found');
    exit;
}

$file_path = $result['srt_file_path'];
$video_title = $result['title'];
$filename = sanitizeFilename($video_title . '_' . $language . '.srt');

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));

// Output file
readfile($file_path);
exit;
?>