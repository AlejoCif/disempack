<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$nombre      = trim($_POST['nombre']      ?? '');
$categoria   = trim($_POST['categoria']   ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if (!$nombre || !$categoria || empty($_FILES['imagen']['name'])) {
    $_SESSION['flash'] = 'ERROR: Completa todos los campos obligatorios.';
    header('Location: dashboard.php');
    exit;
}

$file = $_FILES['imagen'];

// Validar error de subida
if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = 'ERROR: Fallo al subir el archivo (código ' . $file['error'] . ').';
    header('Location: dashboard.php');
    exit;
}

// Validar tamaño (máx 10 MB)
if ($file['size'] > 10 * 1024 * 1024) {
    $_SESSION['flash'] = 'ERROR: La imagen supera los 10 MB.';
    header('Location: dashboard.php');
    exit;
}

// Validar MIME type real
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeReal = $finfo->file($file['tmp_name']);
$mimeMap  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!isset($mimeMap[$mimeReal])) {
    $_SESSION['flash'] = 'ERROR: Solo se permiten imágenes JPG, PNG o WEBP.';
    header('Location: dashboard.php');
    exit;
}

// Nombre de archivo seguro (UUID-like)
$ext      = $mimeMap[$mimeReal];
$filename = bin2hex(random_bytes(16)) . '.' . $ext;

// Guardar fuera de public_html para que el deploy de git no borre el archivo
$destDir  = dirname($_SERVER['DOCUMENT_ROOT']) . '/intranet_media/';
if (!is_dir($destDir) && !mkdir($destDir, 0750, true)) {
    $_SESSION['flash'] = 'ERROR: No se pudo crear el directorio de almacenamiento.';
    header('Location: dashboard.php');
    exit;
}
$destPath = $destDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    $_SESSION['flash'] = 'ERROR: No se pudo guardar la imagen en el servidor.';
    header('Location: dashboard.php');
    exit;
}

// Guardar en base de datos
try {
    $stmt = db()->prepare(
        "INSERT INTO articulos (nombre, categoria, descripcion, archivo, subido_por)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $categoria, $descripcion ?: null, $filename, $_SESSION['usuario_id']]);
    $_SESSION['flash'] = "Artículo \"$nombre\" subido correctamente.";
} catch (PDOException $e) {
    // Si falla la DB, borramos la imagen ya guardada
    @unlink($destPath);
    $_SESSION['flash'] = 'ERROR: No se pudo guardar en la base de datos.';
}

header('Location: dashboard.php');
exit;
