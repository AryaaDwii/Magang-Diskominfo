<?php
// Konfigurasi PDO Database (XAMPP MySQL)
$host = "localhost";
$dbname = "pendidikan_literasi";  // Nama DB-mu (buat dulu di phpMyAdmin kalau belum)
$username = "root";
$password = "";  // Default XAMPP: kosong

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "DB Connected!";  // Test: Uncomment sementara buat cek, hapus setelahnya
} catch(PDOException $e) {
    die("Koneksi DB gagal: " . $e->getMessage());
}
?>