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

$columnsStmt = $pdo->prepare("SHOW COLUMNS FROM $table WHERE Field NOT IN ('id', 'created_at', 'updated_at')");
$columnsStmt->execute();
$columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = [];
    $values = [];
    foreach ($columns as $col) {
        $field = $col['Field'];
        if (isset($_POST[$field])) {
            $fields[] = $field;
            $values[] = $_POST[$field];
        }
    }
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($values)) {
        $success = 'Data berhasil ditambahkan!';
        header('Location: read.php?table=' . $table . '&success=1');
        exit;
    } else {
        $error = 'Gagal menambahkan data!';
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Tambah Data: <?php echo ucwords(str_replace('_', ' ', $table)); ?></h2>
<?php if (isset($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<form method="POST">
    <?php foreach ($columns as $col): ?>
        <div class="mb-3">
            <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col['Field'])); ?></label>
            <?php if (strpos($col['Type'], 'varchar') !== false || strpos($col['Type'], 'text') !== false): ?>
                <input type="text" name="<?php echo $col['Field']; ?>" class="form-control" required>
            <?php elseif (strpos($col['Type'], 'decimal') !== false): ?>
                <input type="number" step="0.0001" name="<?php echo $col['Field']; ?>" class="form-control" required>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-success">Simpan</button>
    <a href="read.php?table=<?php echo $table; ?>" class="btn btn-secondary">Batal</a>
</form>
<?php include '../includes/footer.php'; ?>