<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once 'db.php'; // Koneksi ke database

require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getUserIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'];
}

// Konfigurasi percobaan login
$maxAttempts = 5;
$lockoutTime = 300; // dalam detik (5 menit)

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Cek apakah pengguna sedang diblokir
if ($_SESSION['login_attempts'] >= $maxAttempts) {
    $timeSinceLastAttempt = time() - $_SESSION['last_attempt_time'];

    if ($timeSinceLastAttempt < $lockoutTime) {
        $remainingTime = $lockoutTime - $timeSinceLastAttempt;
        echo json_encode([
            'status' => 'error',
            'blocked' => true,
            'remainingTime' => $remainingTime,
            'message' => "Terlalu banyak percobaan gagal. Coba lagi dalam $remainingTime detik."
        ]);
        exit;
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

function logLoginAttempt($conn, $input, $status)
{
    $ip = getUserIP();
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $attemptTime = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, status, attempt_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $input, $ip, $agent, $status, $attemptTime);
    $stmt->execute();
    $stmt->close();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['usernameOrEmail'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($input) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username/email dan password wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id, username, email, password, is_verified FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (!password_verify($password, $user['password'])) {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            logLoginAttempt($conn, $input, 'failed');
            echo json_encode(['status' => 'error', 'message' => 'Password salah.']);
            exit;
        }

        if (!$user['is_verified']) {
            logLoginAttempt($conn, $input, 'failed');
            echo json_encode(['status' => 'error', 'message' => 'Akun belum diverifikasi. Silakan cek email.']);
            exit;
        }

        // Login berhasil
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_attempts'] = 0;
        logLoginAttempt($conn, $input, 'success');

        // Kirim OTP
        $otp = rand(100000, 999999);
        $otpExpiry = date('Y-m-d H:i:s', time() + 300); // 5 menit

        $update = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE user_id = ?");
        $update->bind_param("ssi", $otp, $otpExpiry, $user['user_id']);
        $update->execute();
        $update->close();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dewitasarip20@gmail.com';
            $mail->Password = 'yfio cnkr flpn xabj'; // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('dewitasarip20@gmail.com', 'Vault Team');
            $mail->addAddress($user['email']); // kirim ke email user

            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Login Vault';
            $mail->Body    = "Hello {$user['username']},<br>Kode OTP login kamu adalah: <strong>$otp</strong><br>Berlaku selama 5 menit.";

            $mail->send();

            echo json_encode([
                'status' => 'otp_required',
                'message' => 'OTP telah dikirim ke email.',
                'redirect' => '/otp-verification.html'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengirim OTP. Silakan coba lagi.'
            ]);
        }
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        logLoginAttempt($conn, $input, 'failed');
        echo json_encode(['status' => 'error', 'message' => 'Akun tidak ditemukan.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak diizinkan.']);
}
