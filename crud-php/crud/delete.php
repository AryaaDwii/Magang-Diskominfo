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

$stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
if ($stmt->execute([$id])) {
    header('Location: read.php?table=' . $table . '&deleted=1');
} else {
    header('Location: read.php?table=' . $table . '&error=1');
}
exit;
?>