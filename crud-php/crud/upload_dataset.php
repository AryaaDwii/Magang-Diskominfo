<?php
require_once '../config/db.php';
session_start();

$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $nama = $_POST['nama_dataset'] ?? 'Dataset Baru';
        $tahun = $_POST['tahun'] ?? date('Y');
        $table_name = strtolower(str_replace(' ', '_', $nama)) . '_' . $tahun;  // e.g., 'pembangunan_literasi_2024'

        // Baca CSV
        if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            $header = fgetcsv($handle, 1000, ',');  // Asumsi delimiter koma
            $rows = 0;
            $data = [];
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $data[] = $row;
                $rows++;
            }
            fclose($handle);

            if ($rows > 0) {
                try {
                    // Buat table dynamic (asumsi kolom: provinsi, kolom_numeric_1, dll. – sesuaikan CSV)
                    $create_sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provinsi VARCHAR(100),
                        indeks DECIMAL(10,4),
                        kolom2 DECIMAL(10,4),
                        kolom3 DECIMAL(10,4)
                        -- Tambah kolom sesuai header CSV
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    $pdo->exec($create_sql);

                    // Insert data (contoh sederhana, sesuaikan kolom)
                    $insert_sql = "INSERT INTO `$table_name` (provinsi, indeks, kolom2, kolom3) VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($insert_sql);
                    foreach ($data as $row) {
                        $stmt->execute([$row[0] ?? '', $row[1] ?? 0, $row[2] ?? 0, $row[3] ?? 0]);  // Asumsi 4 kolom
                    }

                    // Tambah ke table 'datasets'
                    $insert_meta = $pdo->prepare("INSERT INTO datasets (nama, table_name, row_count, tahun) VALUES (?, ?, ?, ?)");
                    $insert_meta->execute([$nama, $table_name, $rows, $tahun]);

                    $message = "Upload sukses! $rows baris diimpor ke table '$table_name'. Kembali ke dashboard?";
                } catch (PDOException $e) {
                    $error = "Error DB: " . $e->getMessage();
                }
            } else {
                $error = "CSV kosong atau gak valid.";
            }
        } else {
            $error = "Gagal baca file CSV.";
        }
    } else {
        $error = "Error upload: " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dataset Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <h2><i class="fas fa-upload me-2"></i>Upload Dataset CSV Baru</h2>
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <a href="index.php" class="btn btn-primary">Kembali ke Dashboard</a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="card p-4">
            <div class="mb-3">
                <label class="form-label">Nama Dataset</label>
                <input type="text" name="nama_dataset" class="form-control" required placeholder="e.g., Rata-rata Lama Sekolah 2024">
            </div>
            <div class="mb-3">
                <label class="form-label">Tahun Data</label>
                <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">File CSV</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <small class="text-muted">Format: Header pertama jadi kolom, delimiter koma.</small>
            </div>
            <button type="submit" class="btn btn-success">Upload & Impor ke DB</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>