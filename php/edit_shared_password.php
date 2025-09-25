<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'], $input['title'], $input['username'], $input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$id = (int)$input['id'];
$title = trim($input['title']);
$username_pw = trim($input['username']);
$password = trim($input['password']);
$url = trim($input['url'] ?? '');
$notes = trim($input['notes'] ?? '');

$sql = "UPDATE shared_passwords 
        SET title = ?, username = ?, password = ?, url = ?, notes = ?, updated_at = NOW(), updated_by = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sssssis", $title, $username_pw, $password, $url, $notes, $user_id, $id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or no changes made']);
}

$stmt->close();
$conn->close();
