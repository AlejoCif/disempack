<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$esAdmin = $_SESSION['usuario_rol'] === 'admin';
$flash   = $_SESSION['flash_perfil'] ?? '';
unset($_SESSION['flash_perfil']);

try {
    $stmt = db()->prepare("SELECT nombre, email, rol, creado_en FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die('Error: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mi Perfil - Disempack Intranet</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Open Sans', sans-serif; background: #f0f2f8; min-height: 100vh; color: #333; }

    .topbar {
      background: #1c2f5e; color: #fff;
      display: flex; align-items: center; justify-content: space-between;
      padding: .8rem 2.5rem; position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 12px rgba(0,0,0,.2);
    }
    .topbar-left { display: flex; align-items: center; gap: 1rem; }
    .topbar-left img { height: 40px; object-fit: contain; }
    .topbar-left .badge {
      background: #8dc63f; color: #fff; font-size: .7rem; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase; padding: .25rem .75rem; border-radius: 100px;
    }
    .topbar-right { display: flex; align-items: center; gap: 1rem; }
    .top-nav-btn {
      background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2);
      border-radius: 6px; padding: .4rem .9rem; font-size: .8rem; font-family: inherit;
      cursor: pointer; text-decoration: none; transition: background .15s;
      display: flex; align-items: center; gap: .4rem;
    }
    .top-nav-btn:hover { background: rgba(255,255,255,.22); }
    .top-nav-btn.active { background: rgba(141,198,63,.25); border-color: #8dc63f; color: #8dc63f; }

    .main { max-width: 600px; margin: 3rem auto; padding: 0 1.5rem; }

    .profile-card {
      background: #fff; border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,.07); overflow: hidden; margin-bottom: 1.5rem;
    }
    .profile-header {
      background: #1c2f5e; padding: 2rem; display: flex; align-items: center; gap: 1.2rem;
    }
    .profile-avatar {
      width: 60px; height: 60px; border-radius: 50%;
      background: #8dc63f; display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; color: #fff; font-weight: 800; flex-shrink: 0;
    }
    .profile-info h2 { font-size: 1.15rem; font-weight: 700; color: #fff; }
    .profile-info p  { font-size: .83rem; color: #c5d4f0; margin-top: .2rem; }
    .profile-body { padding: 1.5rem 2rem; }
    .info-row { display: flex; justify-content: space-between; align-items: center; padding: .6rem 0; border-bottom: 1px solid #f0f0f0; font-size: .88rem; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #888; font-weight: 600; }
    .info-val   { color: #333; }
    .rol-badge  { font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; padding: .25rem .7rem; border-radius: 100px; }
    .rol-admin  { background: #e8f0ff; color: #1c2f5e; }
    .rol-viewer { background: #f0f9e8; color: #4a7c20; }

    .pass-card {
      background: #fff; border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,.07); padding: 2rem;
    }
    .pass-card h3 { font-size: 1rem; font-weight: 700; color: #1c2f5e; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem; }
    .field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: 1.1rem; }
    label { font-size: .75rem; font-weight: 700; color: #1c2f5e; text-transform: uppercase; letter-spacing: .06em; }
    input[type="password"] {
      border: 2px solid #e0e4ef; border-radius: 6px; padding: .72rem 1rem;
      font-size: .9rem; font-family: inherit; color: #333; outline: none;
      transition: border-color .2s;
    }
    input[type="password"]:focus { border-color: #1c2f5e; }
    .hint { font-size: .75rem; color: #999; margin-top: .2rem; }
    .pwd-wrap { position: relative; }
    .pwd-wrap input { padding-right: 2.8rem; width: 100%; }
    .pwd-toggle { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #aaa; font-size: 1rem; padding: 0; display: flex; align-items: center; }
    .pwd-toggle:hover { color: #555; }
    .btn-save {
      background: #8dc63f; color: #fff; font-weight: 700; font-size: .93rem;
      padding: .8rem 2rem; border-radius: 100px; border: none; cursor: pointer;
      font-family: inherit; transition: background .2s, transform .15s; margin-top: .5rem;
    }
    .btn-save:hover { background: #7ab535; transform: translateY(-1px); }

    .flash { border-radius: 6px; padding: .75rem 1rem; margin-bottom: 1.2rem; font-size: .88rem; display: flex; align-items: center; gap: .5rem; }
    .flash.ok  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .flash.err { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <img src="https://disempack.com.co/wp-content/uploads/2025/11/LOGO-WEB-HEAD-BLANCO-e1768929160286-1024x403.png" alt="Disempack"/>
    <span class="badge">Intranet</span>
  </div>
  <div class="topbar-right">
    <a href="dashboard.php" class="top-nav-btn"><i class="fa-solid fa-images"></i> Artículos</a>
    <?php if ($esAdmin): ?>
    <a href="usuarios.php" class="top-nav-btn"><i class="fa-solid fa-users"></i> Usuarios</a>
    <?php endif; ?>
    <a href="perfil.php" class="top-nav-btn active"><i class="fa-solid fa-circle-user"></i> Mi Perfil</a>
    <a href="logout.php"  class="top-nav-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </div>
</div>

<div class="main">

  <?php if ($flash): ?>
    <div class="flash <?= str_starts_with($flash, 'ERROR') ? 'err' : 'ok' ?>">
      <i class="fa-solid <?= str_starts_with($flash, 'ERROR') ? 'fa-circle-xmark' : 'fa-circle-check' ?>"></i>
      <?= htmlspecialchars($flash) ?>
    </div>
  <?php endif; ?>

  <!-- INFO -->
  <div class="profile-card">
    <div class="profile-header">
      <div class="profile-avatar"><?= mb_strtoupper(mb_substr($user['nombre'], 0, 1)) ?></div>
      <div class="profile-info">
        <h2><?= htmlspecialchars($user['nombre']) ?></h2>
        <p><?= htmlspecialchars($user['email']) ?></p>
      </div>
    </div>
    <div class="profile-body">
      <div class="info-row">
        <span class="info-label">Rol</span>
        <span class="rol-badge rol-<?= $user['rol'] ?>"><?= $user['rol'] === 'admin' ? 'Admin' : 'Viewer' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Miembro desde</span>
        <span class="info-val"><?= date('d/m/Y', strtotime($user['creado_en'])) ?></span>
      </div>
    </div>
  </div>

  <!-- CAMBIAR CONTRASEÑA -->
  <div class="pass-card">
    <h3><i class="fa-solid fa-lock" style="color:#8dc63f;"></i> Cambiar contraseña</h3>
    <form method="POST" action="perfil_accion.php" onsubmit="return validar()">
      <div class="field">
        <label for="password_actual">Contraseña actual *</label>
        <div class="pwd-wrap">
          <input type="password" id="password_actual" name="password_actual" placeholder="••••••••" required/>
          <button type="button" class="pwd-toggle" onclick="togglePwd(this)" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
        </div>
      </div>
      <div class="field">
        <label for="password_nueva">Nueva contraseña *</label>
        <div class="pwd-wrap">
          <input type="password" id="password_nueva" name="password_nueva" placeholder="••••••••" required minlength="8"/>
          <button type="button" class="pwd-toggle" onclick="togglePwd(this)" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
        </div>
        <span class="hint">Mínimo 8 caracteres.</span>
      </div>
      <div class="field">
        <label for="password_confirmar">Confirmar nueva contraseña *</label>
        <div class="pwd-wrap">
          <input type="password" id="password_confirmar" name="password_confirmar" placeholder="••••••••" required minlength="8"/>
          <button type="button" class="pwd-toggle" onclick="togglePwd(this)" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-save"><i class="fa-solid fa-check"></i> Actualizar contraseña</button>
    </form>
  </div>

</div>

<script>
  function togglePwd(btn) {
    const input = btn.closest('.pwd-wrap').querySelector('input');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    btn.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
  }
  function validar() {
    const nueva = document.getElementById('password_nueva').value;
    const conf  = document.getElementById('password_confirmar').value;
    if (nueva !== conf) { alert('Las contraseñas nuevas no coinciden.'); return false; }
    return true;
  }
</script>
</body>
</html>
