<?php
require_once 'config/database.php';  // Pakai PDO

// Contoh seeding untuk table rls_data (sesuaikan kolom dari CSV)
$csvFile = '../dataset/Rata-rata Lama Sekolah (RLS) menurut Jenis Kelamin, 2024.csv';  // Path relatif
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    fgetcsv($handle);  // Skip header
    while (($data = fgetcsv($handle)) !== FALSE) {
        $stmt = $pdo->prepare("INSERT INTO rls_data (provinsi, laki_laki, perempuan, rata_rata) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data[0], $data[1] ?? 0, $data[2] ?? 0, $data[3] ?? 0]);
    }
    fclose($handle);
    echo "Seeding selesai! " . ($pdo->query("SELECT COUNT(*) FROM rls_data")->fetchColumn()) . " rows.";
} else {
    echo "File CSV gak ketemu.";
}
?>