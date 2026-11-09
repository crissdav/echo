<div class="error-full">
  <div class="brand brand-lg mb-4"><?php require BASE_PATH . '/app/Views/partials/logo.php'; ?><span class="wordmark">3CH0</span></div>
  <div class="error-icon"><i class="bi bi-cloud-slash"></i></div>
  <h1><?= e($title) ?></h1>
  <p><?= e($detail) ?></p>
  <?php if (!empty($debug)): ?><pre class="debug"><?= e($debug) ?></pre><?php endif; ?>
  <a href="/" class="btn btn-echo mt-2">Reintentar</a>
</div>
