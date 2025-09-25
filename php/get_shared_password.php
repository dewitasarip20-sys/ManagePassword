<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua data dari shared_passwords, ditambah nama user dari tabel users
$sql = "
SELECT sp.id, sp.title, sp.username, sp.password, sp.url, sp.notes,
       sp.created_by, sp.created_at, sp.updated_by, sp.updated_at,
       u1.username AS created_by_username,
       u2.username AS updated_by_username
FROM shared_passwords sp
LEFT JOIN users u1 ON sp.created_by = u1.user_id
LEFT JOIN users u2 ON sp.updated_by = u2.user_id
ORDER BY sp.updated_at DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $passwords = [];
    while ($row = $result->fetch_assoc()) {
        $passwords[] = $row;
    }
    echo json_encode(['success' => true, 'passwords' => $passwords]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tidak ada data ditemukan']);
}

$conn->close();
