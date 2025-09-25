<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session tidak ditemukan.']);
    exit;
}

$otp = $_POST['otp'] ?? '';

if (empty($otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode OTP wajib diisi.']);
    exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Format OTP tidak valid.']);
    exit;
}

session_regenerate_id(true);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (is_null($row['otp_code'])) {
        echo json_encode(['status' => 'error', 'message' => 'Kode OTP tidak tersedia. Silakan minta ulang.']);
        exit;
    }

    $expired = strtotime($row['otp_expiry']) < time();
    if ($row['otp_code'] === $otp && !$expired) {
        $_SESSION['is_verified_otp'] = true;

        $update = $conn->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE user_id = ?");
        $update->bind_param("i", $userId);
        $update->execute();
        $update->close();

        echo json_encode(['status' => 'success', 'message' => 'Verifikasi berhasil']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'OTP salah atau telah kedaluwarsa.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
}

$stmt->close();
$conn->close();