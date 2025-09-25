<?php
date_default_timezone_set('Asia/Jakarta');
include 'db.php'; // koneksi mysqli kamu
require '../vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak valid.");
}

$email = $_POST['email'] ?? '';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (!$email || !$recaptchaResponse) {
    die("Email dan reCAPTCHA wajib diisi.");
}

// 1. Verifikasi reCAPTCHA
$recaptchaSecret = '6LfUM0YrAAAAABdcIG_J_vtVxj2nMrKMOxGfRdKY';
$response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
$responseKeys = json_decode($response, true);
if (empty($responseKeys['success']) || $responseKeys['success'] !== true) {
    die("reCAPTCHA tidak valid.");
}

// 2. Cek apakah email ada di database
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("Email tidak ditemukan.");
}

// 3. Buat token reset
$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

// 4. Hapus token lama untuk user ini (opsional tapi disarankan)
$stmt = $conn->prepare("DELETE FROM password_reset_token WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed (DELETE): " . $conn->error);
}
$stmt->bind_param("i", $user['user_id']);
if (!$stmt->execute()) {
    die("Execute failed (DELETE): " . $stmt->error);
}
$stmt->close();

// 5. Simpan token ke tabel reset_password_tokens
$stmt = $conn->prepare("INSERT INTO password_reset_token (user_id, token, expires_at) VALUES (?, ?, ?)");
if (!$stmt) {
    die("Prepare failed (INSERT): " . $conn->error);
}
$stmt->bind_param("iss", $user['user_id'], $token, $expiry);
if (!$stmt->execute()) {
    die("Execute failed (INSERT): " . $stmt->error);
}
$stmt->close();

// 6. Kirim email dengan PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'dewitasarip20@gmail.com'; // Ganti dengan email kamu
    $mail->Password = 'yfio cnkr flpn xabj';     // Ganti dengan App Password kamu
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('dewitasarip20@gmail.com', 'Vault Admin');
    $mail->addAddress($email);

    $link = "http://localhost/managePass/resetForm.php?token=$token";
    $mail->isHTML(true);
    $mail->Subject = 'Vault Password Reset';
    $mail->Body = "
        <p>Hello,</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='$link'>$link</a></p>
        <p>If you did not request a password reset, please ignore this email.</p>
        <p>Thank you,<br>Vault Admin Team</p>
    ";

    $mail->send();
    echo "Link reset password telah dikirim ke email.";
} catch (Exception $e) {
    echo "Gagal mengirim email. Error: {$mail->ErrorInfo}";
}
