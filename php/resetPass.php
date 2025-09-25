<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

// Konfigurasi database
$host = 'localhost';
$db   = 'webPass'; // Nama database
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

header('Content-Type: application/json'); // Supaya fetch() bisa baca JSON

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi DB gagal: " . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';

    if (!$token || !$new_password || !$confirm_password) {
        echo json_encode([
            "success" => false,
            "message" => "Data belum lengkap."
        ]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode([
            "success" => false,
            "message" => "Password dan konfirmasi tidak cocok."
        ]);
        exit;
    }

    // Ambil token dari tabel password_reset_token
    $stmt = $pdo->prepare("SELECT * FROM password_reset_token WHERE token = ?");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();

    if (!$token_data) {
        echo json_encode([
            "success" => false,
            "message" => "Token tidak ditemukan."
        ]);
        exit;
    }

    if ($token_data['expires_at'] <= date('Y-m-d H:i:s')) {
        echo json_encode([
            "success" => false,
            "message" => "Token sudah kadaluarsa."
        ]);
        exit;
    }

    // Update password user di tabel users
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashed_password, $token_data['user_id']]);

    // Hapus token setelah digunakan
    $stmt = $pdo->prepare("DELETE FROM password_reset_token WHERE token = ?");
    $stmt->execute([$token]);

    echo json_encode([
        "success" => true,
        "message" => "Password berhasil direset."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    include 'resetForm.php';
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Akses tidak valid."
    ]);
    exit;
}
