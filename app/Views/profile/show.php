<?php
use App\Core\View;

$p = $profile;
?>
<div class="page-profile">
  <header class="profile-head">
    <div class="profile-avatar"><span class="ring ring-lg"><?= avatar($p, 150) ?></span></div>
    <div class="profile-info">
      <div class="profile-row">
        <h1 class="profile-username"><?= e($p['username']) ?></h1>
        <?php if ($isMe): ?>
          <a href="/settings" class="btn btn-soft">Editar perfil</a>
          <a href="/new" class="btn btn-soft d-none d-md-inline-block"><i class="bi bi-plus-lg"></i></a>
        <?php else: ?>
          <?= View::partial('partials/follow_button', ['userId' => $p['id'], 'following' => (bool) $p['is_following']]) ?>
          <?php if ($p['follows_me']): ?><span class="pill">Te sigue</span><?php endif; ?>
        <?php endif; ?>
      </div>
      <ul class="profile-stats">
        <li><strong><?= short_number((int) $p['posts_count']) ?></strong> publicaciones</li>
        <li><a href="/u/<?= e($p['username']) ?>/followers"><strong class="js-followers-count"><?= short_number((int) $p['followers_count']) ?></strong> seguidores</a></li>
        <li><a href="/u/<?= e($p['username']) ?>/following"><strong><?= short_number((int) $p['following_count']) ?></strong> seguidos</a></li>
      </ul>
      <div class="profile-bio">
        <strong><?= e($p['full_name']) ?></strong>
        <?php if ($p['bio']): ?><div><?= render_caption($p['bio']) ?></div><?php endif; ?>
        <?php if ($p['website']):
            $href = preg_match('#^https?://#i', $p['website']) ? $p['website'] : 'https://' . $p['website']; ?>
          <a href="<?= e($href) ?>" class="profile-link" target="_blank" rel="noopener nofollow"><i class="bi bi-link-45deg"></i> <?= e($p['website']) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <nav class="profile-tabs"><span class="active"><i class="bi bi-grid-3x3"></i> PUBLICACIONES</span></nav>

  <?php if ($posts): ?>
    <?= View::partial('partials/grid', ['posts' => $posts]) ?>
  <?php else: ?>
    <div class="empty">
      <div class="empty-icon"><i class="bi bi-camera"></i></div>
      <h3><?= $isMe ? 'Comparte tu primera foto' : 'Aún no hay publicaciones' ?></h3>
      <?php if ($isMe): ?><p>Cuando compartas fotos, aparecerán en tu perfil.</p><a href="/new" class="btn btn-echo">Crear publicación</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
