<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

try {
    $usuarios = db()->query(
        "SELECT id, nombre, email, rol, creado_en FROM usuarios ORDER BY creado_en ASC"
    )->fetchAll();
} catch (PDOException $e) {
    die('Error de base de datos: ' . htmlspecialchars($e->getMessage()));
}

$miId = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios - Disempack Intranet</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Open Sans', sans-serif; background: #f0f2f8; min-height: 100vh; color: #333; }

    /* ── TOPBAR ── */
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
      letter-spacing: .08em; text-transform: uppercase; padding: .25rem .75rem;
      border-radius: 100px;
    }
    .topbar-right { display: flex; align-items: center; gap: 1rem; }
    .topbar-right .user { font-size: .85rem; color: #c5d4f0; }
    .topbar-right .user strong { color: #fff; }
    .top-nav-btn {
      background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2);
      border-radius: 6px; padding: .4rem .9rem; font-size: .8rem; font-family: inherit;
      cursor: pointer; text-decoration: none; transition: background .15s; display: flex; align-items: center; gap: .4rem;
    }
    .top-nav-btn:hover { background: rgba(255,255,255,.22); }
    .top-nav-btn.active { background: rgba(141,198,63,.25); border-color: #8dc63f; color: #8dc63f; }

    /* ── MAIN ── */
    .main { max-width: 1000px; margin: 0 auto; padding: 2rem 2.5rem; }

    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.8rem; flex-wrap: wrap; gap: 1rem; }
    .page-header h1 { font-size: 1.4rem; font-weight: 800; color: #1c2f5e; display: flex; align-items: center; gap: .6rem; }

    .btn-nuevo {
      background: #8dc63f; color: #fff; font-weight: 700; font-size: .88rem;
      padding: .65rem 1.5rem; border-radius: 100px; border: none; cursor: pointer;
      font-family: inherit; display: flex; align-items: center; gap: .5rem;
      transition: background .2s, transform .15s;
    }
    .btn-nuevo:hover { background: #7ab535; transform: translateY(-1px); }

    /* ── FLASH ── */
    .flash {
      border-radius: 6px; padding: .75rem 1rem; margin-bottom: 1.5rem;
      font-size: .88rem; display: flex; align-items: center; gap: .5rem;
    }
    .flash.ok  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .flash.err { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    /* ── TABLE ── */
    .table-wrap {
      background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.07); overflow: hidden;
    }
    table { width: 100%; border-collapse: collapse; }
    thead { background: #1c2f5e; }
    thead th { color: #fff; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: .9rem 1.2rem; text-align: left; }
    tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .12s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #f7f9fc; }
    td { padding: .85rem 1.2rem; font-size: .88rem; color: #444; vertical-align: middle; }
    .td-name { font-weight: 700; color: #1c2f5e; }
    .td-email { color: #666; }

    .rol-badge {
      display: inline-block; font-size: .7rem; font-weight: 700; letter-spacing: .06em;
      text-transform: uppercase; padding: .25rem .7rem; border-radius: 100px;
    }
    .rol-admin  { background: #e8f0ff; color: #1c2f5e; }
    .rol-viewer { background: #f0f9e8; color: #4a7c20; }

    .td-actions { display: flex; gap: .5rem; align-items: center; }
    .btn-edit {
      background: #f0f4ff; color: #1c2f5e; border: none; border-radius: 6px;
      padding: .4rem .85rem; font-size: .78rem; font-weight: 700; cursor: pointer;
      font-family: inherit; display: flex; align-items: center; gap: .35rem;
      transition: background .15s;
    }
    .btn-edit:hover { background: #dce5ff; }
    .btn-del {
      background: #fff0f0; color: #c0392b; border: none; border-radius: 6px;
      padding: .4rem .85rem; font-size: .78rem; font-weight: 700; cursor: pointer;
      font-family: inherit; display: flex; align-items: center; gap: .35rem;
      transition: background .15s;
    }
    .btn-del:hover { background: #fdd; }
    .you-badge {
      font-size: .68rem; background: #fff3cd; color: #856404;
      padding: .15rem .5rem; border-radius: 100px; font-weight: 700;
    }

    /* ── MODAL ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55);
      z-index: 500; align-items: center; justify-content: center; padding: 1rem;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: #fff; border-radius: 12px; width: 100%; max-width: 520px;
      box-shadow: 0 24px 72px rgba(0,0,0,.25); overflow: hidden;
    }
    .modal-header {
      background: #1c2f5e; color: #fff;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.2rem 1.8rem;
    }
    .modal-header h2 { font-size: 1rem; font-weight: 700; }
    .modal-close {
      background: none; border: none; color: #fff; font-size: 1.4rem;
      cursor: pointer; line-height: 1; padding: 0;
    }
    .modal-body { padding: 2rem 1.8rem; }
    .m-field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: 1.1rem; }
    .m-field label { font-size: .75rem; font-weight: 700; color: #1c2f5e; text-transform: uppercase; letter-spacing: .06em; }
    .m-field input, .m-field select {
      border: 2px solid #e0e4ef; border-radius: 6px; padding: .72rem 1rem;
      font-size: .9rem; font-family: inherit; color: #333; outline: none;
      transition: border-color .2s; background: #fff;
    }
    .m-field input:focus, .m-field select:focus { border-color: #1c2f5e; }
    .m-hint { font-size: .75rem; color: #999; margin-top: .25rem; }
    .modal-footer { display: flex; justify-content: flex-end; gap: .8rem; padding: 1rem 1.8rem 1.5rem; }
    .btn-cancel {
      background: #f0f2f8; color: #555; border: none; border-radius: 100px;
      padding: .65rem 1.5rem; font-size: .88rem; font-weight: 700; cursor: pointer; font-family: inherit;
    }
    .btn-cancel:hover { background: #e0e4ef; }
    .btn-save {
      background: #8dc63f; color: #fff; border: none; border-radius: 100px;
      padding: .65rem 1.8rem; font-size: .88rem; font-weight: 700; cursor: pointer; font-family: inherit;
      transition: background .2s;
    }
    .btn-save:hover { background: #7ab535; }

    @media (max-width: 768px) {
      .main { padding: 1.5rem 1rem; }
      .topbar { padding: .7rem 1.2rem; }
      thead th:nth-child(3), td:nth-child(3) { display: none; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-left">
    <img src="https://disempack.com.co/wp-content/uploads/2025/11/LOGO-WEB-HEAD-BLANCO-e1768929160286-1024x403.png" alt="Disempack"/>
    <span class="badge">Intranet</span>
  </div>
  <div class="topbar-right">
    <a href="dashboard.php" class="top-nav-btn"><i class="fa-solid fa-images"></i> Artículos</a>
    <a href="usuarios.php"  class="top-nav-btn active"><i class="fa-solid fa-users"></i> Usuarios</a>
    <a href="logout.php"    class="top-nav-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </div>
</div>

<div class="main">
  <div class="page-header">
    <h1><i class="fa-solid fa-users" style="color:#8dc63f;"></i> Gestión de Usuarios</h1>
    <button class="btn-nuevo" onclick="abrirModalNuevo()">
      <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
    </button>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= str_starts_with($flash, 'ERROR') ? 'err' : 'ok' ?>">
      <i class="fa-solid <?= str_starts_with($flash, 'ERROR') ? 'fa-circle-xmark' : 'fa-circle-check' ?>"></i>
      <?= htmlspecialchars($flash) ?>
    </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Registrado</th>
          <th>Rol</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td class="td-name">
            <?= htmlspecialchars($u['nombre']) ?>
            <?php if ($u['id'] == $miId): ?><span class="you-badge">Tú</span><?php endif; ?>
          </td>
          <td class="td-email"><?= htmlspecialchars($u['email']) ?></td>
          <td><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
          <td><span class="rol-badge rol-<?= $u['rol'] ?>"><?= $u['rol'] === 'admin' ? 'Admin' : 'Viewer' ?></span></td>
          <td>
            <div class="td-actions">
              <button class="btn-edit" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($u)) ?>)">
                <i class="fa-solid fa-pen"></i> Editar
              </button>
              <?php if ($u['id'] != $miId): ?>
              <form method="POST" action="usuario_accion.php" onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($u['nombre'])) ?>? Esta acción no se puede deshacer.');">
                <input type="hidden" name="accion" value="eliminar"/>
                <input type="hidden" name="id" value="<?= $u['id'] ?>"/>
                <button type="submit" class="btn-del"><i class="fa-solid fa-trash"></i> Eliminar</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL NUEVO USUARIO -->
<div class="modal-overlay" id="modalNuevo">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fa-solid fa-user-plus" style="margin-right:.5rem;"></i>Nuevo Usuario</h2>
      <button class="modal-close" onclick="cerrarModal('modalNuevo')">&times;</button>
    </div>
    <form method="POST" action="usuario_accion.php">
      <input type="hidden" name="accion" value="crear"/>
      <div class="modal-body">
        <div class="m-field">
          <label for="n_nombre">Nombre completo *</label>
          <input type="text" id="n_nombre" name="nombre" placeholder="Nombre completo" required/>
        </div>
        <div class="m-field">
          <label for="n_email">Correo electrónico *</label>
          <input type="email" id="n_email" name="email" placeholder="usuario@disempack.com" required/>
        </div>
        <div class="m-field">
          <label for="n_password">Contraseña * (mín. 8 caracteres)</label>
          <input type="password" id="n_password" name="password" placeholder="••••••••" required minlength="8"/>
        </div>
        <div class="m-field">
          <label for="n_rol">Rol *</label>
          <select id="n_rol" name="rol" required>
            <option value="viewer">Viewer — puede ver y subir imágenes</option>
            <option value="admin">Admin — puede además eliminar y gestionar usuarios</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="cerrarModal('modalNuevo')">Cancelar</button>
        <button type="submit" class="btn-save"><i class="fa-solid fa-check"></i> Crear usuario</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR USUARIO -->
<div class="modal-overlay" id="modalEditar">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fa-solid fa-pen" style="margin-right:.5rem;"></i>Editar Usuario</h2>
      <button class="modal-close" onclick="cerrarModal('modalEditar')">&times;</button>
    </div>
    <form method="POST" action="usuario_accion.php">
      <input type="hidden" name="accion" value="editar"/>
      <input type="hidden" name="id" id="e_id"/>
      <div class="modal-body">
        <div class="m-field">
          <label for="e_nombre">Nombre completo *</label>
          <input type="text" id="e_nombre" name="nombre" required/>
        </div>
        <div class="m-field">
          <label for="e_email">Correo electrónico *</label>
          <input type="email" id="e_email" name="email" required/>
        </div>
        <div class="m-field">
          <label for="e_password">Nueva contraseña</label>
          <input type="password" id="e_password" name="password" placeholder="Dejar en blanco para no cambiar" minlength="8"/>
          <span class="m-hint">Solo completa este campo si quieres cambiar la contraseña.</span>
        </div>
        <div class="m-field">
          <label for="e_rol">Rol *</label>
          <select id="e_rol" name="rol" required>
            <option value="viewer">Viewer — puede ver y subir imágenes</option>
            <option value="admin">Admin — puede además eliminar y gestionar usuarios</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="cerrarModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn-save"><i class="fa-solid fa-check"></i> Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
  function abrirModalNuevo() {
    document.getElementById('modalNuevo').classList.add('open');
  }

  function abrirModalEditar(u) {
    document.getElementById('e_id').value      = u.id;
    document.getElementById('e_nombre').value  = u.nombre;
    document.getElementById('e_email').value   = u.email;
    document.getElementById('e_password').value = '';
    document.getElementById('e_rol').value     = u.rol;
    document.getElementById('modalEditar').classList.add('open');
  }

  function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
  }

  // Cerrar al hacer clic fuera
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  });
</script>
</body>
</html>
