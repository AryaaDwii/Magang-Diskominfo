<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$table = $_POST['table'] ?? '';
if (empty($table)) {
    echo json_encode(['error' => 'No table specified']);
    exit;
}

try {
    // Hapus dari table 'datasets'
    $stmt = $pdo->prepare("DELETE FROM datasets WHERE table_name = ?");
    $stmt->execute([$table]);

    // DROP table data di DB
    $pdo->exec("DROP TABLE IF EXISTS `$table`");

    echo json_encode(['success' => true, 'message' => 'Dataset dihapus!']);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>