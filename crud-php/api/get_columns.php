<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$table = $_GET['table'] ?? '';
if (empty($table)) {
    echo json_encode(['success' => false, 'error' => 'No table']);
    exit;
}

try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table`");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'columns' => $columns]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>