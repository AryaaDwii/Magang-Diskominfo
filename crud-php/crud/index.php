<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Query dynamic dari table 'datasets'
$stmt = $pdo->query("SELECT nama, table_name, row_count, tahun FROM datasets ORDER BY nama");
$datasets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback kalau table kosong (tampil pesan)
if (empty($datasets)) {
    $datasets = [];  // Atau redirect ke upload
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard CRUD - Pendidikan Literasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .dataset-card { min-height: 200px; transition: box-shadow 0.3s; }
        .dataset-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .card-body { display: flex; flex-direction: column; justify-content: space-between; }
        .btn-group-vertical { width: 100%; }
        @media (max-width: 768px) { .col-md-6 { margin-bottom: 1rem; } }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-database me-2"></i>Dashboard CRUD</a>
            <div class="navbar-nav ms-auto">
                <a href="../auth/logout.php">Logout (<?= $_SESSION['username'] ?>)</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Dataset Tersedia</h2>
        <p class="text-muted mb-4">Kelola dataset pendidikan yang telah diupload.</p>

        <!-- Grid Dataset Cards -->
        <div class="row g-4">
            <?php foreach ($datasets as $ds): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card dataset-card border-primary h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-file-csv me-2"></i><?= htmlspecialchars($ds['nama']) ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><i class="fas fa-table me-1"></i>Data: <?= $ds['row_count'] ?> baris (Tahun <?= $ds['tahun'] ?>)</p>
                            
                            <!-- Dropdown Kolom Dynamic -->
<div class="mb-3">
    <label class="form-label"><i class="fas fa-columns me-1"></i>Pilih Kolom untuk Analisis</label>
    <select class="form-select" id="kolom-<?= htmlspecialchars($ds['table_name']) ?>">
        <option value="">-- Pilih Kolom --</option>
        <?php
        try {
            $kolom_stmt = $pdo->prepare("SHOW COLUMNS FROM `{$ds['table_name']}`");
            $kolom_stmt->execute();
            $koloms = $kolom_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($koloms as $kolom) {
                // Skip ID/timestamp, tampil nama clean
                if (in_array($kolom['Field'], ['id', 'created_at', 'updated_at'])) continue;
                $label = ucwords(str_replace('_', ' ', $kolom['Field']));
                echo '<option value="' . htmlspecialchars($kolom['Field']) . '">' . htmlspecialchars($label) . '</option>';
            }
        } catch (PDOException $e) {
            echo '<option value="">Error load kolom</option>';  // Fallback kalau table gak ada
        }
        ?>
    </select>
</div>
                            
                            <div class="btn-group-vertical btn-group-sm w-100" role="group">
                                <a href="../api/lihat_data.php?table=<?= htmlspecialchars($ds['table_name']) ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Lihat Data
                                </a>
                                <button class="btn btn-success btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#createModal" onclick="setTableForCreate('<?= htmlspecialchars($ds['table_name']) ?>')">
                                    <i class="fas fa-plus me-1"></i>Tambah Data
                                </button>
                                <button class="btn btn-primary" onclick="analisisKolom('<?= htmlspecialchars($ds['table_name']) ?>')">
                                    <i class="fas fa-chart-line me-1"></i>Analisis Kolom
                                </button>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <button class="btn btn-outline-danger btn-sm w-100" onclick="hapusDataset('<?= htmlspecialchars($ds['table_name']) ?>')">
                                <i class="fas fa-trash me-1"></i>Hapus Dataset
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tombol Upload Baru -->
        <div class="row mt-5">
            <div class="col-12">
                <a href="upload_dataset.php" class="btn btn-success btn-lg w-100">
                    <i class="fas fa-upload me-2"></i>Upload Dataset Baru
                </a>
            </div>
        </div>
    </div>

    <!-- JS untuk Interaksi -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function analisisKolom(table) {
            const kolom = document.getElementById('kolom-' + table).value;
            if (!kolom) {
                alert('Pilih kolom dulu!');
                return;
            }
            window.open('../api/analyze.php?table=' + table + '&column=' + kolom, '_blank');
        }

        function hapusDataset(table) {
    if (confirm('Yakin hapus dataset ' + table + '? Data hilang permanen!')) {
        fetch('../api/hapus_dataset.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'table=' + encodeURIComponent(table)
        }).then(response => response.json()).then(data => {
            if (data.success) {
                location.reload();  // Reload biar kotak hilang
            } else {
                alert('Error: ' + (data.error || 'Gagal hapus'));
            }
        }).catch(err => alert('Network error: ' + err));
    }
}
    </script>
    <!-- JS untuk Interaksi -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentTableForCreate = '';

    // Update: Set table & generate fields dynamic
    function setTableForCreate(table) {
    currentTableForCreate = table;
    document.getElementById('createTable').value = table;
    document.getElementById('createTitle').textContent = 'Tambah Data Baru - ' + table.replace(/_/g, ' ').toUpperCase();
    const body = document.getElementById('createBody');

    body.innerHTML = '<div class="alert alert-info">Loading kolom...</div>';

    fetch('../api/get_columns.php?table=' + encodeURIComponent(table))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.columns.length > 0) {
                body.innerHTML = '';
                let colCount = 0;
                data.columns.forEach(col => {
                    if (col.Field === 'id' || col.Field.endsWith('_at')) return;  // Skip ID/timestamp
                    colCount++;
                    const label = col.Field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());  // Fix nama: e.g., "indeks_pembangunan_literasi_masyarakat" → "Indeks Pembangunan Literasi Masyarakat"
                    const type = (col.Type.includes('decimal') || col.Type.includes('float') || col.Type.includes('int')) ? 'number' : 'text';
                    const step = type === 'number' ? ' step="0.0001"' : '';
                    const minMax = type === 'number' ? ' min="0" max="100"' : '';
                    const required = col.Null === 'NO' ? ' required' : '';

                    body.innerHTML += `
                        <div class="mb-3">
                            <label class="form-label">${label}</label>
                            <input type="${type}" name="${col.Field}" class="form-control" placeholder="e.g., ${label}" ${step} ${minMax} ${required}>
                            <small class="text-muted">Kolom ${col.Type} (opsional kalau gak wajib).</small>
                        </div>
                    `;
                });
                if (colCount === 0) {
                    body.innerHTML = '<div class="alert alert-warning">Gak ada kolom untuk diisi (table kosong).</div>';
                }
            } else {
                body.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Table gak ditemukan') + '</div>';
            }
        }).catch(err => {
            body.innerHTML = '<div class="alert alert-danger">Network error: ' + err + '</div>';
        });
}

    // Handle Submit CREATE (kirim semua fields)
    document.getElementById('createForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');
        formData.append('table', currentTableForCreate);

        fetch('../api/crud.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json()).then(data => {
            if (data.success) {
                alert('Data baru ditambahkan! (' + data.message + ')');
                bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
                location.reload();  // Reload dashboard
            } else {
                alert('Error: ' + (data.error || 'Gagal tambah'));
            }
        }).catch(err => alert('Network error: ' + err));
    });

    // Fungsi lain tetap sama
    function analisisKolom(table) {
        const kolom = document.getElementById('kolom-' + table).value;
        if (!kolom) {
            alert('Pilih kolom dulu!');
            return;
        }
        window.open('../api/analyze.php?table=' + table + '&column=' + kolom, '_blank');
    }

    function hapusDataset(table) {
        if (confirm('Yakin hapus dataset ' + table + '? Data hilang permanen!')) {
            fetch('../api/hapus_dataset.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'table=' + encodeURIComponent(table)
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Gagal hapus'));
                }
            }).catch(err => alert('Network error: ' + err));
        }
    }
</script>
    <!-- Modal CREATE: Form Tambah Data Baru (Dynamic Fields) -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">  <!-- Larger dialog biar muat banyak field -->
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createTitle"><i class="fas fa-plus me-2"></i>Tambah Data Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm">
                <div class="modal-body" id="createBody">
                    <input type="hidden" id="createTable" name="table">
                    <!-- Fields akan di-generate JS secara dynamic -->
                    <div class="alert alert-info">Loading kolom...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>