<?php
use App\Core\Auth;

$me = Auth::user();
?>
<div class="page-narrow">
  <h1 class="page-title">Editar perfil</h1>
  <form method="post" action="/settings" enctype="multipart/form-data" class="settings-card">
    <?= csrf_field() ?>
    <div class="avatar-edit">
      <span class="js-avatar-preview"><?= avatar($me, 64) ?></span>
      <div class="flex-grow-1">
        <div class="uname"><?= e($me['username']) ?></div>
        <div class="muted small"><?= e($me['email']) ?></div>
      </div>
      <label class="btn btn-echo btn-sm mb-0">
        Cambiar foto <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden class="js-avatar-input">
      </label>
    </div>
    <?php if ($me['avatar_key']): ?>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="rm">
        <label class="form-check-label small" for="rm">Quitar foto actual</label>
      </div>
    <?php endif; ?>

    <label class="form-label fw-semibold" for="full_name">Nombre</label>
    <input class="form-control mb-3" id="full_name" name="full_name" value="<?= e($me['full_name']) ?>" maxlength="60" required>

    <label class="form-label fw-semibold" for="website">Sitio web</label>
    <input class="form-control mb-3" id="website" name="website" value="<?= e($me['website'] ?? '') ?>" maxlength="120" placeholder="github.com/usuario">

    <label class="form-label fw-semibold" for="bio">Presentación</label>
    <textarea class="form-control js-caption" id="bio" name="bio" rows="3" maxlength="160"><?= e($me['bio'] ?? '') ?></textarea>
    <div class="text-end muted xsmall mt-1"><span class="js-count"><?= mb_strlen($me['bio'] ?? '') ?></span>/160</div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <a href="/u/<?= e($me['username']) ?>" class="btn btn-soft">Cancelar</a>
      <button class="btn btn-echo px-4">Guardar</button>
    </div>
  </form>
</div>
