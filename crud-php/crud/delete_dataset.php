<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../config/db.php';

$table = $_GET['table'] ?? '';
if (empty($table) || $table === 'users') {
    $error = 'Tabel tidak valid atau sistem!';
} else {
    try {
        // Log aksi (opsional)
        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action, table_name) VALUES (?, 'DELETE', ?)");
        $logStmt->execute([$_SESSION['user_id'], $table]);

        $stmt = $pdo->prepare("DROP TABLE `$table`");
        $stmt->execute();
        $success = "Dataset '$table' dihapus sukses! Tabel dan data hilang permanen.";
    } catch (PDOException $e) {
        $error = 'Gagal hapus: ' . $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="container mt-4">
    <h2>Hapus Dataset</h2>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <a href="index.php" class="btn btn-primary">Kembali ke Dashboard</a>
</div>
<?php include '../includes/footer.php'; ?>