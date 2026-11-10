<?php
use App\Core\Auth;
use App\Models\Social;

$me = Auth::user();
$unread = $me ? Social::unreadCount((int) $me['id']) : 0;
$nav = [
    ['/',              'house-door',  'house-door-fill',  'Inicio'],
    ['/explore',       'search',      'search',           'Explorar'],
    ['/notifications', 'heart',       'heart-fill',       'Notificaciones'],
    ['/new',           'plus-square', 'plus-square-fill', 'Crear'],
];
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
<?php require BASE_PATH . '/app/Views/partials/head.php'; ?>
</head>
<body>
<div class="app-shell">

  <!-- Barra lateral (escritorio / tablet) -->
  <aside class="sidebar">
    <a href="/" class="brand">
      <?php require BASE_PATH . '/app/Views/partials/logo.php'; ?>
      <span class="wordmark">3CH0</span>
    </a>
    <nav class="side-nav">
      <?php foreach ($nav as [$href, $icon, $iconOn, $label]): $on = active($href); ?>
        <a href="<?= $href ?>" class="side-link <?= $on ?>">
          <span class="side-icon">
            <i class="bi bi-<?= $on ? $iconOn : $icon ?>"></i>
            <?php if ($href === '/notifications' && $unread): ?><span class="dot-badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
          </span>
          <span class="side-label"><?= $label ?></span>
        </a>
      <?php endforeach; ?>
      <?php if ($me): ?>
        <a href="/u/<?= e($me['username']) ?>" class="side-link <?= active('/u/' . $me['username']) ?>">
          <span class="side-icon"><?= avatar($me, 26, 'side-avatar') ?></span>
          <span class="side-label">Perfil</span>
        </a>
      <?php endif; ?>
    </nav>
    <div class="side-bottom dropup">
      <button class="side-link w-100 border-0 bg-transparent" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="side-icon"><i class="bi bi-list"></i></span><span class="side-label">Más</span>
      </button>
      <ul class="dropdown-menu shadow border-0 rounded-4 p-2">
        <li><a class="dropdown-item rounded-3 py-2" href="/status"><i class="bi bi-cloud-check me-2"></i>Estado del sistema</a></li>
        <li><a class="dropdown-item rounded-3 py-2" href="/settings"><i class="bi bi-gear me-2"></i>Configuración</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="/logout"><?= csrf_field() ?>
            <button class="dropdown-item rounded-3 py-2"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </div>
  </aside>

  <!-- Barra superior (móvil) -->
  <header class="topbar">
    <a href="/" class="brand"><?php require BASE_PATH . '/app/Views/partials/logo.php'; ?><span class="wordmark">3CH0</span></a>
    <div class="d-flex gap-3 align-items-center">
      <button class="top-icon border-0 bg-transparent p-0" data-bs-toggle="offcanvas" data-bs-target="#moreSheet" aria-controls="moreSheet" aria-label="Más opciones">
        <i class="bi bi-list"></i>
      </button>
    </div>
  </header>

  <main class="main">
    <?php foreach (['success' => 'check-circle-fill', 'error' => 'exclamation-circle-fill'] as $type => $icon): ?>
      <?php if ($msg = flash($type)): ?>
        <div class="flash flash-<?= $type ?>" role="alert"><i class="bi bi-<?= $icon ?>"></i><div><?= $msg ?></div></div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?= $content ?>
  </main>

  <!-- Navegación inferior (móvil) -->
  <nav class="bottomnav" aria-label="Navegación principal">
    <?php foreach ($nav as [$href, $icon, $iconOn, $label]): $on = active($href); ?>
      <a href="<?= $href ?>" class="tab <?= $on ?>" <?= $on ? 'aria-current="page"' : '' ?>>
        <span class="tab-icon">
          <i class="bi bi-<?= $on ? $iconOn : $icon ?>"></i>
          <?php if ($href === '/notifications' && $unread): ?><span class="dot-badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </span>
        <span class="tab-label"><?= $href === '/notifications' ? 'Actividad' : $label ?></span>
      </a>
    <?php endforeach; ?>
    <?php if ($me): $on = active('/u/' . $me['username']); ?>
      <a href="/u/<?= e($me['username']) ?>" class="tab <?= $on ?>" <?= $on ? 'aria-current="page"' : '' ?>>
        <span class="tab-icon"><?= avatar($me, 26) ?></span><span class="tab-label">Perfil</span>
      </a>
    <?php endif; ?>
  </nav>
</div>

<!-- Menú "Más" en móvil (hoja inferior) -->
<div class="offcanvas offcanvas-bottom sheet" tabindex="-1" id="moreSheet" aria-label="Más opciones">
  <div class="sheet-handle" aria-hidden="true"></div>
  <nav class="sheet-group">
    <a class="sheet-link" href="/explore"><i class="bi bi-search"></i>Explorar</a>
    <a class="sheet-link" href="/status"><i class="bi bi-cloud-check"></i>Estado del sistema</a>
    <a class="sheet-link" href="/settings"><i class="bi bi-gear"></i>Configuración</a>
    <form method="post" action="/logout" class="m-0"><?= csrf_field() ?>
      <button class="sheet-link danger"><i class="bi bi-box-arrow-right"></i>Cerrar sesión</button>
    </form>
  </nav>
</div>

<!-- Publicación en modal (se rellena por JS desde /p/{id}) -->
<div class="modal fade post-modal" id="postModal" tabindex="-1" aria-label="Publicación" aria-hidden="true">
  <button type="button" class="modal-close-float d-none d-md-grid" data-bs-dismiss="modal" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header ios-navbar d-md-none">
        <button type="button" class="nav-back" data-bs-dismiss="modal"><i class="bi bi-chevron-left"></i>Atrás</button>
        <h2 class="modal-title">Publicación</h2>
      </div>
      <div class="modal-body js-post-modal-body"></div>
    </div>
  </div>
</div>

<!-- Seguidores / seguidos en modal -->
<div class="modal fade list-modal" id="listModal" tabindex="-1" aria-labelledby="listModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header ios-navbar">
        <h2 class="modal-title" id="listModalTitle">Conexiones</h2>
        <button type="button" class="nav-done" data-bs-dismiss="modal">Listo</button>
      </div>
      <div class="modal-body js-list-modal-body"></div>
    </div>
  </div>
</div>

<div class="toast-zone" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/echo.js') ?>"></script>
</body>
</html>
