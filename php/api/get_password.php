<?php
date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db.php'; // koneksi database

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'personal';

// Kita hanya proses yang personal karena ini untuk `personal_password`
if ($type !== 'personal') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid password type']);
    exit;
}

$stmt = $conn->prepare("SELECT id, title, username, password, url, notes FROM personal_passwords WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

$stmt->execute();
$result = $stmt->get_result();

$passwords = [];
while ($row = $result->fetch_assoc()) {
    $passwords[] = $row;
}

echo json_encode([
    'success' => true,
    'passwords' => $passwords
]);

$stmt->close();
$conn->close();
?>
