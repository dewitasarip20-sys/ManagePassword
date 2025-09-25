<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$id = (int)$input['id'];

// Hapus berdasarkan ID saja (tanpa cek siapa pembuat)
$sql = "DELETE FROM shared_passwords WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Delete failed: ID not found or already deleted',
        'error' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
