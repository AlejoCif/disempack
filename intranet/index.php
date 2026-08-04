<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        try {
            $stmt = db()->prepare("SELECT id, nombre, password_hash, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['usuario_id']     = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                $_SESSION['usuario_rol']    = $user['rol'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión. Verifica la configuración de db.php.';
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intranet - Disempack SAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Open Sans', sans-serif;
      background: linear-gradient(135deg, #1c2f5e 0%, #2a4a8a 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;
    }
    .card {
      background: #fff; border-radius: 12px;
      box-shadow: 0 32px 80px rgba(0,0,0,.3);
      padding: 3.5rem 3.5rem; width: 100%; max-width: 440px;
    }
    .logo { text-align: center; margin-bottom: 2.5rem; }
    .logo img { height: 65px; object-fit: contain; }
    .badge {
      display: inline-block; background: #f0f4ff; color: #1c2f5e;
      font-size: .72rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; padding: .3rem .9rem; border-radius: 100px;
      margin-bottom: 1rem;
    }
    h1 { font-size: 1.6rem; font-weight: 800; color: #1c2f5e; margin-bottom: .3rem; }
    .subtitle { font-size: .88rem; color: #888; margin-bottom: 2rem; }
    .field { display: flex; flex-direction: column; gap: .4rem; margin-bottom: 1.2rem; }
    label { font-size: .78rem; font-weight: 700; color: #1c2f5e; text-transform: uppercase; letter-spacing: .06em; }
    input {
      border: 2px solid #e0e4ef; border-radius: 6px; padding: .78rem 1rem;
      font-size: .93rem; font-family: inherit; color: #333; outline: none;
      transition: border-color .2s;
    }
    input:focus { border-color: #1c2f5e; }
    .btn {
      width: 100%; background: #8dc63f; color: #fff; font-weight: 700;
      font-size: .95rem; padding: .9rem; border-radius: 100px; border: none;
      cursor: pointer; font-family: inherit; margin-top: .5rem;
      transition: background .2s, transform .15s;
    }
    .btn:hover { background: #7ab535; transform: translateY(-1px); }
    .error-msg {
      background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;
      border-radius: 6px; padding: .75rem 1rem; font-size: .85rem;
      margin-bottom: 1.2rem; text-align: center;
    }
    .footer-note { text-align: center; font-size: .75rem; color: #aaa; margin-top: 1.8rem; }
    .pwd-wrap { position: relative; }
    .pwd-wrap input { padding-right: 2.8rem; width: 100%; }
    .pwd-toggle { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #aaa; font-size: 1rem; padding: 0; display: flex; align-items: center; }
    .pwd-toggle:hover { color: #555; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <img src="https://disempack.com.co/wp-content/uploads/2025/11/GENERICO-USOS-scaled.png" alt="Disempack"/>
  </div>
  <div class="badge">Acceso Restringido</div>
  <h1>Intranet</h1>
  <p class="subtitle">Zona exclusiva para el equipo Disempack.</p>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="field">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email" placeholder="usuario@disempack.com" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <div class="pwd-wrap">
        <input type="password" id="password" name="password" placeholder="••••••••" required/>
        <button type="button" class="pwd-toggle" onclick="togglePwd(this)" tabindex="-1">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn">Ingresar →</button>
  </form>
  <p class="footer-note">Disempack SAS · Uso interno</p>
</div>
<script>
  function togglePwd(btn) {
    const input = btn.closest('.pwd-wrap').querySelector('input');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    btn.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
  }
</script>
</body>
</html>
