<?php
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db.php';

// Pastikan header JSON dikirim
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

// Cek kepemilikan
$stmt = $conn->prepare("SELECT user_id FROM personal_passwords WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Password not found']);
    exit;
}

// Hapus data password
$stmt = $conn->prepare("DELETE FROM personal_passwords WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete password']);
}
?>
