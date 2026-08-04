<?php
// ── Configuración de base de datos ──
// Reemplaza estos valores con los datos de tu panel de Hostinger → Bases de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'TU_USUARIO_DB');
define('DB_PASS', 'TU_CONTRASEÑA_DB');
define('DB_NAME', 'TU_NOMBRE_DB');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
