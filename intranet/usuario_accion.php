<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

$accion = $_POST['accion'] ?? '';
$miId   = $_SESSION['usuario_id'];

try {
    $pdo = db();

    switch ($accion) {

        // ── CREAR ──────────────────────────────────────────────
        case 'crear':
            $nombre   = trim($_POST['nombre']   ?? '');
            $email    = trim($_POST['email']    ?? '');
            $password = trim($_POST['password'] ?? '');
            $rol      = in_array($_POST['rol'] ?? '', ['admin','viewer']) ? $_POST['rol'] : 'viewer';

            if (!$nombre || !$email || !$password) {
                $_SESSION['flash'] = 'ERROR: Todos los campos son obligatorios.';
                break;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash'] = 'ERROR: Correo electrónico inválido.';
                break;
            }
            if (strlen($password) < 8) {
                $_SESSION['flash'] = 'ERROR: La contraseña debe tener al menos 8 caracteres.';
                break;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $rol]);
            $_SESSION['flash'] = "Usuario \"$nombre\" creado correctamente.";
            break;

        // ── EDITAR ─────────────────────────────────────────────
        case 'editar':
            $id     = (int)($_POST['id']     ?? 0);
            $nombre = trim($_POST['nombre']  ?? '');
            $email  = trim($_POST['email']   ?? '');
            $password = trim($_POST['password'] ?? '');
            $rol    = in_array($_POST['rol'] ?? '', ['admin','viewer']) ? $_POST['rol'] : 'viewer';

            if (!$id || !$nombre || !$email) {
                $_SESSION['flash'] = 'ERROR: Datos incompletos.';
                break;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash'] = 'ERROR: Correo electrónico inválido.';
                break;
            }
            // No permitir que el admin se quite su propio rol de admin
            if ($id === $miId && $rol !== 'admin') {
                $_SESSION['flash'] = 'ERROR: No puedes quitarte el rol de administrador a ti mismo.';
                break;
            }

            if ($password) {
                if (strlen($password) < 8) {
                    $_SESSION['flash'] = 'ERROR: La nueva contraseña debe tener al menos 8 caracteres.';
                    break;
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, password_hash=?, rol=? WHERE id=?");
                $stmt->execute([$nombre, $email, $hash, $rol, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id=?");
                $stmt->execute([$nombre, $email, $rol, $id]);
            }
            $_SESSION['flash'] = "Usuario \"$nombre\" actualizado correctamente.";
            break;

        // ── ELIMINAR ───────────────────────────────────────────
        case 'eliminar':
            $id = (int)($_POST['id'] ?? 0);

            if (!$id) {
                $_SESSION['flash'] = 'ERROR: ID inválido.';
                break;
            }
            if ($id === $miId) {
                $_SESSION['flash'] = 'ERROR: No puedes eliminarte a ti mismo.';
                break;
            }

            $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if ($user) {
                $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
                $_SESSION['flash'] = "Usuario \"{$user['nombre']}\" eliminado.";
            } else {
                $_SESSION['flash'] = 'ERROR: Usuario no encontrado.';
            }
            break;

        default:
            $_SESSION['flash'] = 'ERROR: Acción desconocida.';
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['flash'] = 'ERROR: Ese correo electrónico ya está registrado.';
    } else {
        $_SESSION['flash'] = 'ERROR: ' . htmlspecialchars($e->getMessage());
    }
}

header('Location: usuarios.php');
exit;
