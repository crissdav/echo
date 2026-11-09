<!doctype html>
<html lang="es">
<head>
<?php require BASE_PATH . '/app/Views/partials/head.php'; ?>
</head>
<body class="guest">
<div class="guest-wrap">
  <section class="guest-hero">
    <div class="ripples" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <div class="hero-inner">
      <div class="brand brand-lg">
        <?php require BASE_PATH . '/app/Views/partials/logo.php'; ?>
        <span class="wordmark">3CH0</span>
      </div>
      <h1>Comparte un momento.<br>Escucha cómo hace <em>eco</em>.</h1>
      <p>La red social de fotos de la comunidad, desplegada en la nube de AWS.</p>
      <ul class="hero-stack">
        <li><i class="bi bi-hdd-rack"></i> Amazon EC2</li>
        <li><i class="bi bi-database"></i> Amazon RDS</li>
        <li><i class="bi bi-images"></i> Amazon S3</li>
      </ul>
    </div>
  </section>
  <section class="guest-form">
    <div class="auth-card">
      <?php foreach (['success' => 'check-circle-fill', 'error' => 'exclamation-circle-fill'] as $type => $icon): ?>
        <?php if ($msg = flash($type)): ?>
          <div class="flash flash-<?= $type ?>" role="alert"><i class="bi bi-<?= $icon ?>"></i><div><?= $msg ?></div></div>
        <?php endif; ?>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
    <p class="guest-foot">3CH0 · Computación en la Nube · FIIS – UNAS · <?= date('Y') ?></p>
  </section>
</div>
<script src="<?= asset('js/echo.js') ?>"></script>
</body>
</html>
