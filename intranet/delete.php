<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT archivo, nombre FROM articulos WHERE id = ?");
    $stmt->execute([$id]);
    $art  = $stmt->fetch();

    if ($art) {
        $filePath = __DIR__ . '/assets/' . $art['archivo'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        $pdo->prepare("DELETE FROM articulos WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = "Artículo \"{$art['nombre']}\" eliminado.";
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = 'ERROR al eliminar: ' . htmlspecialchars($e->getMessage());
}

header('Location: dashboard.php');
exit;
