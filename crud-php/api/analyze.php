<?php
require_once __DIR__ . '/../config/db.php';
require_once '../includes/header.php';

$table = $_GET['table'] ?? '';
$column = $_GET['column'] ?? '';

if (empty($table)) {
    echo "<div class='alert alert-warning'>No table selected.</div>";
    require_once '../includes/footer.php';
    exit;
}

$url = 'http://localhost:5000/analyze/' . $table;
if ($column) $url .= '?column=' . urlencode($column);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $error = json_decode($response, true)['detail'] ?? 'Unknown error';
    echo "<div class='alert alert-danger'>API Error ($httpCode): $error</div>";
    require_once '../includes/footer.php';
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<div class='alert alert-danger'>JSON Parse Error: " . json_last_error_msg() . "</div>";
    require_once '../includes/footer.php';
    exit;
}
?>

<div class="container mt-4">
    <h2>Hasil Analisis: <?php echo htmlspecialchars($table); ?> (Kolom: <?php echo htmlspecialchars($column ?: 'Default'); ?>)</h2>

    <!-- Preview Data -->
    <h4>Preview Data (Seluruh Baris)</h4>
    <?php if (empty($data['preview'])): ?>
        <div class="alert alert-warning">No data tersedia di tabel. Tambah data via CRUD atau upload!</div>
    <?php else: ?>
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Provinsi</th>
                        <th>Kolom Terpilih</th>
                        <th>Total (Last Numeric)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['preview'] as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['provinsi'] ?? $row['rovinsi'] ?? $row['prov'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($row['indeks_pembangunan_literasi_masyarakat'] ?? $row['kolom_terpilih'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($row['jumlah_anggota_perpustakaan'] ?? $row['total'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Chart -->
    <?php if (isset($data['chart']) && $data['chart']): ?>
        <h4>Chart</h4>
        <img src="data:image/png;base64,<?php echo $data['chart']; ?>" class="img-fluid" alt="Chart">
    <?php else: ?>
        <div class="alert alert-warning">No chart - tabel kosong atau no kolom numeric.</div>
    <?php endif; ?>

    <!-- Regresi -->
    <?php if (!empty($data['regresi'])): ?>
        <h4>Regresi Linier (Kolom <?php echo htmlspecialchars($column); ?> vs Total)</h4>
        <p>R-squared: <?php echo number_format($data['regresi']['r_squared'] ?? 0, 4); ?> (Kesesuaian model)</p>
        <p>Koefisien: <?php echo number_format($data['regresi']['koefisien'] ?? 0, 4); ?></p>
        <p>Prediksi Mean: <?php echo number_format($data['regresi']['prediksi_mean'] ?? 0, 2); ?></p>
    <?php else: ?>
        <div class="alert alert-info">No regresi data tersedia.</div>
    <?php endif; ?>

    <!-- Klasifikasi -->
    <?php if (!empty($data['klasifikasi'])): ?>
        <h4>Klasifikasi</h4>
        <ul>
            <?php foreach ($data['klasifikasi'] as $kls): ?>
                <li><?php echo htmlspecialchars($kls['provinsi']); ?>: <?php echo $kls['kategori']; ?> (Rata-rata: <?php echo number_format($kls['rata_rata'], 2); ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Debug -->
    <h4>Debug (Kolom Digunakan)</h4>
    <pre><?php echo json_encode($data['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>

    <a href="javascript:history.back()" class="btn btn-secondary mt-3">Kembali ke Dashboard</a>
</div>

<?php require_once '../includes/footer.php'; ?>