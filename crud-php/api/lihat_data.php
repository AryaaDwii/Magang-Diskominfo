<?php
require_once '../config/db.php';
$table = $_GET['table'] ?? '';
if (empty($table)) {
    die('<div class="alert alert-danger">Table tidak ditemukan! <a href="../crud/index.php">Kembali</a></div>');
}

// Query data (limit 50)
$stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 50");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil nama kolom
$columns = array_keys($rows[0] ?? []);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Data - <?= htmlspecialchars($table) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <h2 class="mb-3"><i class="fas fa-table me-2"></i>Data dari Table: <?= htmlspecialchars($table) ?></h2>
        <a href="../crud/index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard</a>

        <?php if (empty($rows)): ?>
            <div class="alert alert-warning">Table kosong atau tidak ada data. <a href="../crud/create_data.php?table=<?= htmlspecialchars($table) ?>">Tambah Data Baru?</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <?php foreach ($columns as $col): ?>
                                <?php if (!in_array($col, ['id', 'created_at', 'updated_at'])): ?>
                                    <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id'] ?? 'N/A') ?></td>
                                <?php foreach ($row as $key => $value): ?>
                                    <?php if (!in_array($key, ['id', 'created_at', 'updated_at'])): ?>
                                        <td><?= htmlspecialchars($value ?? 'N/A') ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <td>
                                    <button class="btn btn-sm btn-warning me-1" onclick="editRow(<?= htmlspecialchars($row['id'] ?? 0) ?>, '<?= htmlspecialchars($table) ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRow(<?= htmlspecialchars($row['id'] ?? 0) ?>, '<?= htmlspecialchars($table) ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Menampilkan <?= count($rows) ?> baris (limit 50). <a href="../crud/create_data.php?table=<?= htmlspecialchars($table) ?>">Tambah Data Baru</a></p>
        <?php endif; ?>
    </div>

    <!-- Modal UPDATE: Form Edit Data -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm">
                    <div class="modal-body" id="editBody">
                        <input type="hidden" id="editId" name="id">
                        <input type="hidden" id="editTable" name="table">
                        <!-- Fields dynamic akan di-generate JS -->
                        <div class="alert alert-info">Loading kolom...</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Row: Load data & generate fields
        function editRow(id, table) {
            document.getElementById('editId').value = id;
            document.getElementById('editTable').value = table;
            const body = document.getElementById('editBody');

            body.innerHTML = '<div class="alert alert-info">Loading...</div>';

            // Fetch kolom
            fetch('../api/get_columns.php?table=' + encodeURIComponent(table))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        body.innerHTML = '';
                        data.columns.forEach(col => {
                            if (col.Field === 'id' || col.Field.endsWith('_at')) return;
                            const label = col.Field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            const type = (col.Type.includes('decimal') || col.Type.includes('float') || col.Type.includes('int')) ? 'number' : 'text';
                            const step = type === 'number' ? ' step="0.0001"' : '';
                            const minMax = type === 'number' ? ' min="0" max="100"' : '';
                            const required = col.Null === 'NO' ? ' required' : '';

                            body.innerHTML += `
                                <div class="mb-3">
                                    <label class="form-label">${label}</label>
                                    <input type="${type}" name="${col.Field}" class="form-control" placeholder="e.g., ${label}" ${step} ${minMax} ${required}>
                                </div>
                            `;
                        });
                        new bootstrap.Modal(document.getElementById('editModal')).show();
                        loadRowData(id, table);  // Load data existing
                    } else {
                        body.innerHTML = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                    }
                }).catch(err => body.innerHTML = '<div class="alert alert-danger">Network error: ' + err + '</div>');
        }

        // Load existing row data for edit
        function loadRowData(id, table) {
            fetch('../api/crud.php?action=get_row&table=' + encodeURIComponent(table) + '&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.row) {
                        Object.keys(data.row).forEach(key => {
                            const input = document.querySelector(`input[name="${key}"]`);
                            if (input) input.value = data.row[key] || '';
                        });
                    }
                }).catch(err => alert('Error load data: ' + err));
        }

        // Delete Row
        function deleteRow(id, table) {
            if (confirm('Yakin hapus row ID ' + id + '? Data hilang permanen!')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('table', table);
                formData.append('id', id);

                fetch('../api/crud.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        alert('Data dihapus!');
                        location.reload();  // Reload table
                    } else {
                        alert('Error: ' + (data.error || 'Gagal hapus'));
                    }
                }).catch(err => alert('Network error: ' + err));
            }
        }

        // Handle Submit UPDATE
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('../api/crud.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    alert('Data updated!');
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Gagal update'));
                }
            }).catch(err => alert('Network error: ' + err));
        });
    </script>
</body>
</html>