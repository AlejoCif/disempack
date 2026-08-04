<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

$id       = $_SESSION['usuario_id'];
$actual   = trim($_POST['password_actual']    ?? '');
$nueva    = trim($_POST['password_nueva']     ?? '');
$confirmar = trim($_POST['password_confirmar'] ?? '');

try {
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($actual, $user['password_hash'])) {
        $_SESSION['flash_perfil'] = 'ERROR: La contraseña actual es incorrecta.';
        header('Location: perfil.php');
        exit;
    }

    if (strlen($nueva) < 8) {
        $_SESSION['flash_perfil'] = 'ERROR: La nueva contraseña debe tener al menos 8 caracteres.';
        header('Location: perfil.php');
        exit;
    }

    if ($nueva !== $confirmar) {
        $_SESSION['flash_perfil'] = 'ERROR: Las contraseñas no coinciden.';
        header('Location: perfil.php');
        exit;
    }

    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id=?")->execute([$hash, $id]);
    $_SESSION['flash_perfil'] = 'Contraseña actualizada correctamente.';

} catch (PDOException $e) {
    $_SESSION['flash_perfil'] = 'ERROR: ' . htmlspecialchars($e->getMessage());
}

header('Location: perfil.php');
exit;
