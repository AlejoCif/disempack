<?php
// ════════════════════════════════════════════════════════════
//  SETUP — Ejecutar UNA sola vez, luego ELIMINAR este archivo
// ════════════════════════════════════════════════════════════

// Lock file: bloquea ejecuciones simultáneas o repetidas
// aunque unlink() falle por permisos, este archivo detiene todo
$lockFile = __DIR__ . '/setup.lock';

if (file_exists($lockFile)) {
    http_response_code(403);
    die('Setup ya fue ejecutado. Este script está deshabilitado. Elimina setup.php del servidor manualmente.');
}

require_once 'db.php';

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$nombre || !$email || !$password) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Crear lock ANTES de tocar la DB para cerrar la ventana de doble ejecución
        file_put_contents($lockFile, date('Y-m-d H:i:s'));

        $setupExitoso = false;
        try {
            $pdo = db();

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id            INT AUTO_INCREMENT PRIMARY KEY,
                    nombre        VARCHAR(100)  NOT NULL,
                    email         VARCHAR(255)  NOT NULL UNIQUE,
                    password_hash VARCHAR(255)  NOT NULL,
                    rol           ENUM('admin','viewer') DEFAULT 'viewer',
                    creado_en     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS articulos (
                    id          INT AUTO_INCREMENT PRIMARY KEY,
                    nombre      VARCHAR(255) NOT NULL,
                    categoria   VARCHAR(100),
                    descripcion TEXT,
                    archivo     VARCHAR(255) NOT NULL,
                    subido_por  INT,
                    creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$nombre, $email, $hash]);

            $setupExitoso = true;

        } catch (PDOException $e) {
            // Si algo falló, eliminamos el lock para permitir reintentar
            @unlink($lockFile);

            if ($e->getCode() == 23000) {
                $error = 'Ese correo ya existe en la base de datos.';
            } else {
                $error = 'Error de base de datos: ' . htmlspecialchars($e->getMessage());
            }
        }

        if ($setupExitoso) {
            // Intentar auto-borrado. Si falla por permisos, el lock ya protege.
            @unlink(__FILE__);
            $mensaje = '¡Listo! Tablas y administrador creados. setup.php ha sido eliminado automáticamente. <strong>Verifica en el File Manager que el archivo ya no existe — si sigue ahí, bórralo manualmente ahora.</strong>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Setup - Disempack Intranet</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Open Sans', sans-serif; background: #f7f9fc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .card { background: #fff; border-radius: 10px; box-shadow: 0 24px 72px -12px rgba(0,0,0,.12); padding: 3rem 3.5rem; width: 100%; max-width: 480px; }
    .logo { text-align: center; margin-bottom: 2rem; }
    .logo img { height: 60px; }
    h1 { font-size: 1.5rem; font-weight: 800; color: #1c2f5e; margin-bottom: .4rem; text-align: center; }
    .warning { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: .8rem 1rem; font-size: .83rem; color: #856404; margin-bottom: 1.8rem; text-align: center; }
    .field { display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.2rem; }
    label { font-size: .78rem; font-weight: 700; color: #1c2f5e; text-transform: uppercase; letter-spacing: .05em; }
    input { border: 2px solid #e0e4ef; border-radius: 6px; padding: .75rem 1rem; font-size: .93rem; font-family: inherit; color: #333; outline: none; transition: border-color .2s; }
    input:focus { border-color: #1c2f5e; }
    .btn { width: 100%; background: #8dc63f; color: #fff; font-weight: 700; font-size: .95rem; padding: .85rem; border-radius: 100px; border: none; cursor: pointer; font-family: inherit; margin-top: .5rem; transition: background .2s; }
    .btn:hover { background: #7ab535; }
    .msg-ok  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 6px; padding: .9rem 1rem; margin-top: 1.2rem; font-size: .88rem; text-align: center; }
    .msg-err { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 6px; padding: .9rem 1rem; margin-top: 1.2rem; font-size: .88rem; text-align: center; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <img src="https://disempack.com.co/wp-content/uploads/2025/11/GENERICO-USOS-scaled.png" alt="Disempack"/>
  </div>
  <h1>Configuración Inicial</h1>
  <div class="warning">⚠️ Ejecuta esto <strong>una sola vez</strong> y luego verifica que setup.php haya sido eliminado del servidor.</div>

  <?php if ($mensaje): ?>
    <div class="msg-ok"><?= $mensaje ?></div>
  <?php else: ?>
    <?php if ($error): ?><div class="msg-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="field">
        <label for="nombre">Nombre del administrador</label>
        <input type="text" id="nombre" name="nombre" placeholder="Nombre completo" required/>
      </div>
      <div class="field">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" placeholder="admin@disempack.com" required/>
      </div>
      <div class="field">
        <label for="password">Contraseña (mín. 8 caracteres)</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required/>
      </div>
      <button type="submit" class="btn">Crear base de datos y administrador</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
