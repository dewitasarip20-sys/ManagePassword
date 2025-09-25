<?php
session_start(); // <-- Harus paling atas

// Debug log sementara (bisa dihapus di production)
file_put_contents('session_debug.log', json_encode($_SESSION));
file_put_contents('debug.log', file_get_contents('php://input'));

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Cek apakah user login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username_session = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title'] ?? '');
$username_pw = trim($data['username'] ?? '');
$password_pw = trim($data['password'] ?? '');
$url = trim($data['url'] ?? '');
$notes = trim($data['notes'] ?? '');

// Validasi sederhana
if ($title === '' || $password_pw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title dan password wajib diisi']);
    exit;
}

// Simpan ke DB
$stmt = $conn->prepare("INSERT INTO shared_passwords 
    (title, username, password, url, notes, created_by, created_at, updated_by, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())");
$stmt->bind_param("ssssiii", $title, $username_pw, $password_pw, $url, $notes, $username_session, $username_session);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan password', 'error' => $stmt->error]);
}
