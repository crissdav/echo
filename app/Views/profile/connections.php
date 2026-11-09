<?php
use App\Core\Auth;
use App\Core\View;
?>
<div class="page-narrow">
  <a href="/u/<?= e($profile['username']) ?>" class="back-link"><i class="bi bi-arrow-left"></i> @<?= e($profile['username']) ?></a>
  <div class="seg-tabs">
    <a href="/u/<?= e($profile['username']) ?>/followers" class="<?= $type === 'followers' ? 'active' : '' ?>"><?= (int) $profile['followers_count'] ?> seguidores</a>
    <a href="/u/<?= e($profile['username']) ?>/following" class="<?= $type === 'following' ? 'active' : '' ?>"><?= (int) $profile['following_count'] ?> seguidos</a>
  </div>
  <div class="card-list">
    <?php foreach ($people as $u): ?>
      <div class="person">
        <a href="/u/<?= e($u['username']) ?>"><?= avatar($u, 44) ?></a>
        <div class="flex-grow-1 min-w-0">
          <a href="/u/<?= e($u['username']) ?>" class="uname d-block"><?= e($u['username']) ?></a>
          <div class="muted small text-truncate"><?= e($u['full_name']) ?></div>
        </div>
        <?php if ((int) $u['id'] !== Auth::id()): ?>
          <?= View::partial('partials/follow_button', ['userId' => $u['id'], 'following' => (bool) $u['is_following'], 'size' => 'btn-follow-sm']) ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$people): ?><div class="empty py-4"><p class="mb-0">No hay nadie aquí todavía.</p></div><?php endif; ?>
  </div>
</div>
