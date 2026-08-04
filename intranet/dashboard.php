<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$esAdmin   = $_SESSION['usuario_rol'] === 'admin';
$nombre    = $_SESSION['usuario_nombre'];
$flash     = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Filtros
$filtroCat = $_GET['cat'] ?? '';
$filtroBusq = trim($_GET['q'] ?? '');

try {
    $pdo = db();

    // Categorías para filtro
    $cats = $pdo->query("SELECT DISTINCT categoria FROM articulos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

    // Consulta artículos
    $where  = [];
    $params = [];
    if ($filtroCat)  { $where[] = 'a.categoria = ?';        $params[] = $filtroCat; }
    if ($filtroBusq) { $where[] = 'a.nombre LIKE ?';        $params[] = "%$filtroBusq%"; }
    $sql = "SELECT a.*, u.nombre AS subido_por_nombre
            FROM articulos a
            LEFT JOIN usuarios u ON u.id = a.subido_por"
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY a.creado_en DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articulos = $stmt->fetchAll();

    // Total general
    $total = $pdo->query("SELECT COUNT(*) FROM articulos")->fetchColumn();
} catch (PDOException $e) {
    die('Error de base de datos: ' . htmlspecialchars($e->getMessage()));
}

$categorias = [
    'P.O.P', 'Señalización Corporativa', 'Alcancías', 'Recubrimientos',
    'Impresión Digital', 'Organizadores', 'Cajas Plegadizas', 'Blister',
    'Clam Shell', 'Diseño y Desarrollo', 'Termoformado', 'Troquelado',
    'Termosellado', 'Corte CNC', 'Acondicionamiento Productos', 'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Intranet - Disempack SAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Open Sans', sans-serif; background: #f0f2f8; min-height: 100vh; color: #333; }

    /* ── TOP BAR ── */
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
    .topbar-right { display: flex; align-items: center; gap: 1.5rem; }
    .topbar-right .user { font-size: .85rem; color: #c5d4f0; }
    .topbar-right .user strong { color: #fff; }
    .top-nav-btn {
      background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2);
      border-radius: 6px; padding: .4rem .9rem; font-size: .8rem; font-family: inherit;
      cursor: pointer; text-decoration: none; transition: background .15s; display: flex; align-items: center; gap: .4rem;
    }
    .top-nav-btn:hover { background: rgba(255,255,255,.22); }
    .top-nav-btn.active { background: rgba(141,198,63,.25); border-color: #8dc63f; color: #8dc63f; }

    /* ── MAIN LAYOUT ── */
    .main { max-width: 1400px; margin: 0 auto; padding: 2rem 2.5rem; }

    /* ── STATS ── */
    .stats { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .stat-card {
      background: #fff; border-radius: 8px; padding: 1.2rem 1.8rem;
      box-shadow: 0 2px 8px rgba(0,0,0,.06); display: flex; align-items: center; gap: 1rem;
      min-width: 160px;
    }
    .stat-icon { font-size: 1.6rem; }
    .stat-num  { font-size: 1.8rem; font-weight: 800; color: #1c2f5e; line-height: 1; }
    .stat-label { font-size: .75rem; color: #888; margin-top: .1rem; }

    /* ── FLASH ── */
    .flash {
      background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
      border-radius: 6px; padding: .75rem 1rem; margin-bottom: 1.5rem;
      font-size: .88rem; display: flex; align-items: center; gap: .5rem;
    }

    /* ── UPLOAD PANEL ── */
    .upload-panel {
      background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06);
      margin-bottom: 2rem; overflow: hidden;
    }
    .upload-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.1rem 1.8rem; cursor: pointer; border-bottom: 1px solid #eee;
      user-select: none;
    }
    .upload-header h2 { font-size: 1rem; font-weight: 700; color: #1c2f5e; display: flex; align-items: center; gap: .6rem; }
    .upload-header .toggle-icon { color: #8dc63f; font-size: .9rem; transition: transform .25s; }
    .upload-header.open .toggle-icon { transform: rotate(180deg); }
    .upload-body { padding: 1.8rem; display: none; }
    .upload-body.open { display: block; }

    .up-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem; }
    .up-field { display: flex; flex-direction: column; gap: .35rem; }
    .up-field.full { grid-column: 1 / -1; }
    .up-field label { font-size: .75rem; font-weight: 700; color: #1c2f5e; text-transform: uppercase; letter-spacing: .06em; }
    .up-field input[type="text"],
    .up-field select,
    .up-field textarea {
      border: 2px solid #e0e4ef; border-radius: 6px; padding: .7rem 1rem;
      font-size: .9rem; font-family: inherit; color: #333; outline: none;
      transition: border-color .2s; background: #fff;
    }
    .up-field input:focus, .up-field select:focus, .up-field textarea:focus { border-color: #1c2f5e; }
    .up-field textarea { resize: vertical; min-height: 80px; }

    .drop-zone {
      border: 2px dashed #c0c8e0; border-radius: 8px; padding: 2.5rem 1rem;
      text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
      background: #f7f9fc;
    }
    .drop-zone:hover, .drop-zone.drag { border-color: #8dc63f; background: #f0f9e8; }
    .drop-zone i { font-size: 2rem; color: #8dc63f; margin-bottom: .5rem; display: block; }
    .drop-zone p { font-size: .88rem; color: #666; }
    .drop-zone strong { color: #1c2f5e; }
    .drop-zone input[type="file"] { display: none; }
    .file-preview { margin-top: .8rem; font-size: .82rem; color: #555; }

    .up-btn {
      background: #8dc63f; color: #fff; font-weight: 700; font-size: .93rem;
      padding: .8rem 2.5rem; border-radius: 100px; border: none; cursor: pointer;
      font-family: inherit; transition: background .2s, transform .15s; margin-top: .5rem;
    }
    .up-btn:hover { background: #7ab535; transform: translateY(-1px); }
    .up-btn:disabled { background: #b0c880; cursor: not-allowed; transform: none; }

    /* ── FILTERS ── */
    .filters {
      display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
    }
    .filter-search {
      display: flex; align-items: center; gap: .5rem;
      background: #fff; border: 2px solid #e0e4ef; border-radius: 6px;
      padding: .55rem 1rem; flex: 1; min-width: 200px; max-width: 320px;
    }
    .filter-search input {
      border: none; outline: none; font-size: .9rem; font-family: inherit;
      color: #333; width: 100%; background: transparent;
    }
    .filter-search i { color: #aaa; font-size: .9rem; }
    .filter-select {
      border: 2px solid #e0e4ef; border-radius: 6px; padding: .55rem 1rem;
      font-size: .88rem; font-family: inherit; color: #333; outline: none;
      background: #fff; cursor: pointer;
    }
    .filter-count { font-size: .83rem; color: #888; margin-left: auto; }

    /* ── GALLERY ── */
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 1.2rem;
    }
    .art-card {
      background: #fff; border-radius: 10px; overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,.07);
      transition: box-shadow .2s, transform .2s;
      display: flex; flex-direction: column;
    }
    .art-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.14); transform: translateY(-2px); }
    .art-img {
      width: 100%; aspect-ratio: 4/3; object-fit: cover;
      display: block; cursor: zoom-in; transition: filter .25s;
    }
    .art-img:hover { filter: brightness(.88); }
    .art-body { padding: 1rem 1.1rem; flex: 1; display: flex; flex-direction: column; gap: .3rem; }
    .art-cat {
      font-size: .7rem; font-weight: 700; color: #8dc63f;
      text-transform: uppercase; letter-spacing: .06em;
    }
    .art-name { font-size: .92rem; font-weight: 700; color: #1c2f5e; line-height: 1.3; }
    .art-desc { font-size: .78rem; color: #777; line-height: 1.5; flex: 1; }
    .art-meta { font-size: .72rem; color: #aaa; margin-top: .4rem; }
    .art-footer { padding: .7rem 1.1rem; border-top: 1px solid #f0f0f0; display: flex; gap: .5rem; }
    .btn-download {
      flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
      background: #f0f4ff; color: #1c2f5e; font-size: .78rem; font-weight: 700;
      padding: .5rem; border-radius: 6px; text-decoration: none; transition: background .15s;
    }
    .btn-download:hover { background: #dce5ff; }
    .btn-delete {
      display: flex; align-items: center; justify-content: center;
      background: #fff0f0; color: #c0392b; font-size: .82rem;
      padding: .5rem .8rem; border-radius: 6px; border: none; cursor: pointer;
      transition: background .15s; font-family: inherit;
    }
    .btn-delete:hover { background: #fdd; }

    /* ── EMPTY ── */
    .empty { text-align: center; padding: 4rem 2rem; color: #aaa; }
    .empty i { font-size: 3rem; margin-bottom: 1rem; display: block; }
    .empty p { font-size: .95rem; }

    /* ── LIGHTBOX ── */
    .lightbox {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,.88);
      z-index: 999; align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 90vw; max-height: 88vh; border-radius: 4px; object-fit: contain; }
    .lightbox-close {
      position: absolute; top: 1.2rem; right: 1.5rem; color: #fff; font-size: 2rem;
      cursor: pointer; background: none; border: none; line-height: 1;
    }

    @media (max-width: 768px) {
      .main { padding: 1.5rem 1rem; }
      .up-grid { grid-template-columns: 1fr; }
      .topbar { padding: .7rem 1.2rem; }
      .topbar-left .badge { display: none; }
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
    <span class="user">Hola, <strong><?= htmlspecialchars($nombre) ?></strong>
      <?php if ($esAdmin): ?><small style="color:#8dc63f;margin-left:.3rem;">Admin</small><?php endif; ?>
    </span>
    <a href="dashboard.php" class="top-nav-btn active"><i class="fa-solid fa-images"></i> Artículos</a>
    <?php if ($esAdmin): ?>
    <a href="usuarios.php" class="top-nav-btn"><i class="fa-solid fa-users"></i> Usuarios</a>
    <?php endif; ?>
    <a href="perfil.php"   class="top-nav-btn"><i class="fa-solid fa-circle-user"></i> Mi Perfil</a>
    <a href="logout.php"   class="top-nav-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </div>
</div>

<div class="main">

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div>
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-label">Artículos registrados</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🗂️</div>
      <div>
        <div class="stat-num"><?= count($cats) ?></div>
        <div class="stat-label">Categorías</div>
      </div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <!-- UPLOAD PANEL -->
  <div class="upload-panel">
    <div class="upload-header" id="uploadToggle">
      <h2><i class="fa-solid fa-cloud-arrow-up" style="color:#8dc63f;"></i> Subir nuevo artículo</h2>
      <i class="fa-solid fa-chevron-down toggle-icon"></i>
    </div>
    <div class="upload-body" id="uploadBody">
      <form method="POST" action="upload.php" enctype="multipart/form-data" id="uploadForm">
        <div class="up-grid">
          <div class="up-field">
            <label for="art_nombre">Nombre del artículo *</label>
            <input type="text" id="art_nombre" name="nombre" placeholder="Ej: Blister PET 15x20 cm" required/>
          </div>
          <div class="up-field">
            <label for="art_cat">Categoría *</label>
            <select id="art_cat" name="categoria" required>
              <option value="">Selecciona una categoría</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="up-field full">
            <label for="art_desc">Descripción (opcional)</label>
            <textarea id="art_desc" name="descripcion" placeholder="Materiales, medidas, características especiales…"></textarea>
          </div>
          <div class="up-field full">
            <label>Imagen del artículo * (JPG, PNG, WEBP — máx. 10 MB)</label>
            <div class="drop-zone" id="dropZone">
              <i class="fa-solid fa-image"></i>
              <p>Arrastra la imagen aquí o <strong>haz clic para seleccionar</strong></p>
              <p class="file-preview" id="filePreview"></p>
              <input type="file" name="imagen" id="fileInput" accept=".jpg,.jpeg,.png,.webp" required/>
            </div>
          </div>
        </div>
        <button type="submit" class="up-btn" id="submitBtn">
          <i class="fa-solid fa-cloud-arrow-up"></i> Subir artículo
        </button>
      </form>
    </div>
  </div>

  <!-- FILTERS -->
  <div class="filters">
    <form method="GET" style="display:contents;">
      <div class="filter-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Buscar artículo…" value="<?= htmlspecialchars($filtroBusq) ?>"/>
      </div>
      <select class="filter-select" name="cat" onchange="this.form.submit()">
        <option value="">Todas las categorías</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $filtroCat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" style="display:none;"></button>
    </form>
    <span class="filter-count"><?= count($articulos) ?> resultado<?= count($articulos) !== 1 ? 's' : '' ?></span>
  </div>

  <!-- GALLERY -->
  <?php if (empty($articulos)): ?>
    <div class="empty">
      <i class="fa-solid fa-box-open"></i>
      <p>No hay artículos aún. ¡Sube el primero!</p>
    </div>
  <?php else: ?>
  <div class="gallery">
    <?php foreach ($articulos as $art): ?>
    <div class="art-card">
      <img class="art-img" src="assets/<?= htmlspecialchars($art['archivo']) ?>"
           alt="<?= htmlspecialchars($art['nombre']) ?>"
           onclick="openLightbox(this.src)"/>
      <div class="art-body">
        <?php if ($art['categoria']): ?>
          <div class="art-cat"><?= htmlspecialchars($art['categoria']) ?></div>
        <?php endif; ?>
        <div class="art-name"><?= htmlspecialchars($art['nombre']) ?></div>
        <?php if ($art['descripcion']): ?>
          <div class="art-desc"><?= nl2br(htmlspecialchars($art['descripcion'])) ?></div>
        <?php endif; ?>
        <div class="art-meta">
          Subido por <?= htmlspecialchars($art['subido_por_nombre'] ?? 'desconocido') ?>
          · <?= date('d/m/Y H:i', strtotime($art['creado_en'])) ?>
        </div>
      </div>
      <div class="art-footer">
        <a class="btn-download" href="assets/<?= htmlspecialchars($art['archivo']) ?>" download="<?= htmlspecialchars($art['nombre']) ?>">
          <i class="fa-solid fa-download"></i> Descargar
        </a>
        <?php if ($esAdmin): ?>
        <form method="POST" action="delete.php" onsubmit="return confirm('¿Eliminar este artículo?');">
          <input type="hidden" name="id" value="<?= $art['id'] ?>"/>
          <button type="submit" class="btn-delete"><i class="fa-solid fa-trash"></i></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
  <img id="lightboxImg" src="" alt=""/>
</div>

<script>
  // ── UPLOAD TOGGLE ──
  const toggle = document.getElementById('uploadToggle');
  const body   = document.getElementById('uploadBody');
  toggle.addEventListener('click', () => {
    const open = body.classList.toggle('open');
    toggle.classList.toggle('open', open);
  });

  // ── DROP ZONE ──
  const dropZone   = document.getElementById('dropZone');
  const fileInput  = document.getElementById('fileInput');
  const filePreview = document.getElementById('filePreview');

  dropZone.addEventListener('click', () => fileInput.click());

  dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
  dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag');
    const dt = e.dataTransfer;
    if (dt.files.length) {
      fileInput.files = dt.files;
      filePreview.textContent = dt.files[0].name;
    }
  });

  fileInput.addEventListener('change', () => {
    filePreview.textContent = fileInput.files[0]?.name ?? '';
  });

  // ── SUBMIT BUTTON ──
  document.getElementById('uploadForm').addEventListener('submit', () => {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo…';
  });

  // ── LIGHTBOX ──
  function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
  }
  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
  }
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLightbox(); });

  // ── FILTRO LIVE SEARCH ──
  const searchInput = document.querySelector('.filter-search input');
  searchInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') searchInput.closest('form').submit();
  });
</script>
</body>
</html>
