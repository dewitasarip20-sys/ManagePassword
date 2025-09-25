<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? 0;
$title = $data['title'] ?? '';
$username_pw = $data['username'] ?? '';
$password_pw = $data['password'] ?? '';
$url = $data['url'] ?? '';
$notes = $data['notes'] ?? '';

// Pastikan password yang diedit milik user
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

// Update password
$stmt = $conn->prepare("UPDATE personal_passwords SET title = ?, username = ?, password = ?, url = ?, notes = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("sssssi", $title, $username_pw, $password_pw, $url, $notes, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update password']);
}
?>
