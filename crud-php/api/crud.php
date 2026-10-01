<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$table = $_POST['table'] ?? '';
if (empty($action) || empty($table)) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    if ($_GET['action'] === 'get_row') {
    $id = $_GET['id'] ?? 0;
    $table = $_GET['table'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'row' => $row ?: []]);
    exit;
}
    switch ($action) {
        case 'create':
            $provinsi = $_POST['provinsi'] ?? '';
            $indeks = $_POST['indeks'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO `$table` (provinsi, indeks_pembangunan_literasi_masyarakat) VALUES (?, ?)");
            $stmt->execute([$provinsi, $indeks]);
            echo json_encode(['success' => true, 'message' => 'Data created!']);
            break;
        // Tambah case 'update' & 'delete' kalau belum ada dari iterasi sebelumnya
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>