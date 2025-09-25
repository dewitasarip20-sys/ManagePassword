<?php
$host = 'localhost';        // atau IP server database
$db   = 'webPass';          // nama database baru
$user = 'root';             // username MySQL kamu
$pass = '';                 // password MySQL (kosong kalau default XAMPP)

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
