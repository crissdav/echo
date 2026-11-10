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
      <a href="/status" class="top-icon" aria-label="Estado del sistema"><i class="bi bi-cloud-check"></i></a>
      <a href="/notifications" class="top-icon" aria-label="Notificaciones">
        <i class="bi bi-heart"></i><?php if ($unread): ?><span class="dot-badge"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
      </a>
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
  <nav class="bottomnav">
    <?php foreach ($nav as [$href, $icon, $iconOn, $label]): $on = active($href); if ($href === '/notifications') continue; ?>
      <a href="<?= $href ?>" class="<?= $on ?>" aria-label="<?= $label ?>"><i class="bi bi-<?= $on ? $iconOn : $icon ?>"></i></a>
    <?php endforeach; ?>
    <?php if ($me): ?><a href="/u/<?= e($me['username']) ?>" aria-label="Perfil"><?= avatar($me, 26) ?></a><?php endif; ?>
  </nav>
</div>

<div class="toast-zone" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/echo.js') ?>"></script>
</body>
</html>
