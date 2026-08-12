<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}

$f = basename($_GET['f'] ?? '');
// Solo nombres generados por upload.php: 32 hex + extensión válida
if (!$f || !preg_match('/^[0-9a-f]{32}\.(jpg|jpeg|png|webp)$/i', $f)) {
    http_response_code(400);
    exit;
}

$path = dirname($_SERVER['DOCUMENT_ROOT']) . '/intranet_media/' . $f;
if (!file_exists($path)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
$download = !empty($_GET['dl']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');

if ($download) {
    $name = $_GET['name'] ?? $f;
    header('Content-Disposition: attachment; filename="' . rawurlencode(basename($name)) . '"');
} else {
    header('Content-Disposition: inline');
}

readfile($path);
