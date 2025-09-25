<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$username_pw = trim($data['username'] ?? '');
$password_pw = trim($data['password'] ?? '');
$url = trim($data['url'] ?? '');
$notes = trim($data['notes'] ?? '');

if ($title === '' || $password_pw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title dan password wajib diisi']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO personal_passwords 
(user_id, title, username, password, url, notes, created_at, updated_at) 
VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");

$stmt->bind_param("isssss", 
    $user_id, 
    $title, 
    $username_pw, 
    $password_pw, 
    $url, 
    $notes
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan password']);
}
?>
