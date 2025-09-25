<?php
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/register_error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// PHPMailer
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
    echo json_encode(["success" => false, "message" => "Semua field harus diisi."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit;
}

if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    echo json_encode(['success' => false, 'message' => 'Password harus minimal 8 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(["success" => false, "message" => "Password dan konfirmasi tidak cocok."]);
    exit;
}

$check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username atau email sudah terdaftar."]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Generate token dan hash password
$token = bin2hex(random_bytes(32)); // token unik
$is_verified = 0;

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password, verifikasi_token, is_verified) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $username, $email, $hashedPassword, $token, $is_verified);

if ($stmt->execute()) {
    // Kirim email verifikasi
    $verifyLink = "http://localhost/managePass/php/verify.php?token=$token"; // Ganti sesuai domain kamu
    $subject = "Verifikasi Email Vault";
    $body = "Halo $username,<br><br>Terima kasih sudah mendaftar.<br>Silakan klik link berikut untuk memverifikasi akunmu:<br><br><a href='$verifyLink'>$verifyLink</a><br><br>Salam, Vault Team";

    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // atau smtp.domainmu
        $mail->SMTPAuth = true;
        $mail->Username = 'dewitasarip20@gmail.com'; // Ganti dengan email kamu
        $mail->Password = 'yfio cnkr flpn xabj'; // Gunakan App Password Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('dewitasarip20@gmail.com', 'Vault Team');
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo json_encode(["success" => true, "message" => "Registrasi berhasil! Cek email untuk verifikasi."]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Gagal kirim email verifikasi."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Registrasi gagal."]);
}

$stmt->close();
$conn->close();
?>
