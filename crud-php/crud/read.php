<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$table = $_GET['table'] ?? '';
if (empty($table)) {
    header('Location: index.php');
    exit;
}
include '../config/db.php';

// Dapatkan kolom tabel untuk dynamic form (sederhana, ambil semua kolom kecuali id/ timestamps)
$columnsStmt = $pdo->prepare("SHOW COLUMNS FROM $table");
$columnsStmt->execute();
$columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
$displayColumns = array_filter($columns, function($col) {
    return !in_array($col['Field'], ['id', 'created_at', 'updated_at']);
});

// Ambil data
$stmt = $pdo->prepare("SELECT * FROM $table ORDER BY id DESC");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../includes/header.php'; ?>
<h2>Data: <?php echo ucwords(str_replace('_', ' ', $table)); ?></h2>
<a href="create.php?table=<?php echo $table; ?>" class="btn btn-success mb-3">Tambah Data Baru</a>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <?php foreach ($displayColumns as $col): ?>
                <th><?php echo ucwords(str_replace('_', ' ', $col['Field'])); ?></th>
            <?php endforeach; ?>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <?php foreach ($displayColumns as $col): ?>
                    <td><?php echo $row[$col['Field']]; ?></td>
                <?php endforeach; ?>
                <td>
                    <a href="update.php?id=<?php echo $row['id']; ?>&table=<?php echo $table; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?php echo $row['id']; ?>&table=<?php echo $table; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>