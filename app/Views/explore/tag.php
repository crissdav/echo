<?php
use App\Core\View;
?>
<div class="page-wide">
  <header class="tag-head">
    <div class="tag-badge">#</div>
    <div>
      <h1>#<?= e($tag) ?></h1>
      <p><strong><?= count($posts) ?></strong> <?= count($posts) === 1 ? 'publicación' : 'publicaciones' ?></p>
    </div>
  </header>
  <?php if ($posts): ?>
    <?= View::partial('partials/grid', ['posts' => $posts]) ?>
  <?php else: ?>
    <div class="empty"><div class="empty-icon"><i class="bi bi-hash"></i></div><h3>Sin publicaciones</h3><p>Sé el primero en usar #<?= e($tag) ?>.</p></div>
  <?php endif; ?>
</div>
