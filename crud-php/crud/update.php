<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';
if (empty($table) || empty($id)) {
    header('Location: index.php');
    exit;
}
include '../config/db.php';

$columnsStmt = $pdo->prepare("SHOW COLUMNS FROM $table WHERE Field NOT IN ('id', 'created_at', 'updated_at')");
$columnsStmt->execute();
$columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data existing
$stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: read.php?table=' . $table);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = [];
    $values = [];
    foreach ($columns as $col) {
        $field = $col['Field'];
        if (isset($_POST[$field])) {
            $fields[] = $field . ' = ?';
            $values[] = $_POST[$field];
        }
    }
    $values[] = $id;  // Untuk WHERE
    $sql = "UPDATE $table SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($values)) {
        header('Location: read.php?table=' . $table . '&success=1');
        exit;
    } else {
        $error = 'Gagal update data!';
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Edit Data: <?php echo $row['provinsi'] ?? 'ID ' . $id; ?></h2>
<?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<form method="POST">
    <?php foreach ($columns as $col): ?>
        <div class="mb-3">
            <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col['Field'])); ?></label>
            <?php if (strpos($col['Type'], 'varchar') !== false || strpos($col['Type'], 'text') !== false): ?>
                <input type="text" name="<?php echo $col['Field']; ?>" value="<?php echo htmlspecialchars($row[$col['Field']] ?? ''); ?>" class="form-control" required>
            <?php elseif (strpos($col['Type'], 'decimal') !== false): ?>
                <input type="number" step="0.0001" name="<?php echo $col['Field']; ?>" value="<?php echo $row[$col['Field']] ?? ''; ?>" class="form-control" required>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-warning">Update</button>
    <a href="read.php?table=<?php echo $table; ?>" class="btn btn-secondary">Batal</a>
</form>
<?php include '../includes/footer.php'; ?>