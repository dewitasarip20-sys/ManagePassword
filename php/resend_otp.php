<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php'; // Sesuaikan path

// 🔗 Koneksi ke database
require './db.php'; // Sesuaikan dengan file koneksi DB kamu

// 1. Cek session email
if (!isset($_SESSION['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session tidak ditemukan. Silakan login ulang.'
    ]);
    exit;
}

$email = $_SESSION['email'];
$otp = rand(100000, 999999);
$expire = date('Y-m-d H:i:s', time() + 300); // 5 menit ke depan

// 2. Simpan ke database
try {
    $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $expire, $email);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Pengguna tidak ditemukan atau data tidak diubah.'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal update OTP ke database: ' . $e->getMessage()
    ]);
    exit;
}

// 3. Kirim email pakai PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'dewitasarip20@gmail.com';
    $mail->Password   = 'yfio cnkr flpn xabj';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('dewitasarip20@gmail.com', 'Nama Aplikasi');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Kode OTP Baru Anda';
    $mail->Body    = "<p>Kode OTP Anda: <strong>$otp</strong></p><p>Berlaku sampai: <strong>$expire</strong></p>";

    $mail->send();

    echo json_encode([
        'status' => 'success',
        "duration" => 180, // dalam detik (3 menit)
        'message' => 'Kode OTP berhasil dikirim dan disimpan ke database.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengirim email. ' . $mail->ErrorInfo
    ]);
}
