<?php
use App\Core\View;
?>
<div class="page-wide">
  <form class="search-bar" action="/explore" method="get" role="search">
    <i class="bi bi-search"></i>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar personas o #hashtags" autocomplete="off" aria-label="Buscar">
  </form>

  <?php if ($q !== ''): ?>
    <h2 class="section-title">Resultados para “<?= e($q) ?>”</h2>
    <div class="card-list">
      <?php foreach ($results as $u): ?>
        <div class="person">
          <a href="/u/<?= e($u['username']) ?>"><?= avatar($u, 44) ?></a>
          <div class="flex-grow-1 min-w-0">
            <a href="/u/<?= e($u['username']) ?>" class="uname d-block"><?= e($u['username']) ?></a>
            <div class="muted small text-truncate"><?= e($u['full_name']) ?> · <?= short_number((int) $u['followers_count']) ?> seguidores</div>
          </div>
          <?php if ((int) $u['id'] !== \App\Core\Auth::id()): ?>
            <?= View::partial('partials/follow_button', ['userId' => $u['id'], 'following' => (bool) $u['is_following'], 'size' => 'btn-follow-sm']) ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$results): ?><div class="empty py-4"><p class="mb-0">No encontramos cuentas con ese nombre.</p></div><?php endif; ?>
    </div>
  <?php else: ?>

    <?php if ($tags): ?>
      <div class="chips">
        <span class="chips-label"><i class="bi bi-graph-up-arrow"></i> Tendencias</span>
        <?php foreach ($tags as $t): ?>
          <a href="/tag/<?= e(rawurlencode($t['tag'])) ?>" class="chip">#<?= e($t['tag']) ?> <small><?= (int) $t['uses'] ?></small></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($people): ?>
      <h2 class="section-title">Descubre personas</h2>
      <div class="people-row">
        <?php foreach ($people as $u): ?>
          <div class="person-card">
            <a href="/u/<?= e($u['username']) ?>"><?= avatar($u, 72) ?></a>
            <a href="/u/<?= e($u['username']) ?>" class="uname text-truncate w-100"><?= e($u['username']) ?></a>
            <span class="muted xsmall text-truncate w-100"><?= e($u['full_name']) ?></span>
            <?= View::partial('partials/follow_button', ['userId' => $u['id'], 'following' => false, 'size' => 'btn-follow-sm w-100']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 class="section-title">Lo más popular</h2>
    <?= View::partial('partials/grid', ['posts' => $posts, 'mosaic' => true]) ?>
  <?php endif; ?>
</div>
