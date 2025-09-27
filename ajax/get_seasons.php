<?php
// ajax/get_seasons.php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$series_id = isset($_GET['series_id']) ? (int)$_GET['series_id'] : 0;

if (!$series_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid series ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, season_number, title FROM seasons WHERE series_id = ? AND status = 'active' ORDER BY season_number");
$stmt->execute([$series_id]);
$seasons = $stmt->fetchAll();

echo json_encode(['success' => true, 'seasons' => $seasons]);
?>