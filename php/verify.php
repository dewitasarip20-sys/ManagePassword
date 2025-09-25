<?php
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT user_id FROM users WHERE verifikasi_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verifikasi_token = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    echo "Email berhasil diverifikasi.";
} else {
    echo "Token tidak valid atau sudah digunakan.";
}

if (!$token) {
    die("Token tidak valid.");
}

$stmt = $conn->prepare("SELECT user_id FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
    $update->bind_param("i", $user['user_id']);
    $update->execute();
    echo "Akun kamu berhasil diverifikasi. Silakan login.";
} else {
    echo "Token tidak ditemukan atau sudah digunakan.";
}

$stmt->close();
$conn->close();
?>
