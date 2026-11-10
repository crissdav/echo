<?php
use App\Core\Storage;
use App\Core\View;

$texts = ['like' => 'le gustó tu publicación.', 'comment' => 'comentó tu publicación.', 'follow' => 'comenzó a seguirte.'];
$icons = ['like' => 'heart-fill', 'comment' => 'chat-fill', 'follow' => 'person-plus-fill'];
?>
<div class="page-narrow">
  <h1 class="page-title">Notificaciones</h1>
  <?php if (!$groups): ?>
    <div class="empty"><div class="empty-icon"><i class="bi bi-heart"></i></div><h3>Nada por aquí</h3><p>Cuando alguien interactúe con tus publicaciones, lo verás aquí.</p></div>
  <?php endif; ?>
  <?php foreach ($groups as $label => $items): ?>
    <h2 class="notif-group"><?= $label ?></h2>
    <div class="card-list">
      <?php foreach ($items as $n): ?>
        <div class="notif <?= $n['is_read'] ? '' : 'unread' ?>">
          <a href="/u/<?= e($n['username']) ?>" class="notif-avatar">
            <?= avatar($n, 44) ?>
            <span class="notif-type t-<?= $n['type'] ?>"><i class="bi bi-<?= $icons[$n['type']] ?>"></i></span>
          </a>
          <div class="flex-grow-1 small">
            <a href="/u/<?= e($n['username']) ?>" class="uname"><?= e($n['username']) ?></a> <?= $texts[$n['type']] ?>
            <span class="muted"><?= time_ago($n['created_at']) ?></span>
          </div>
          <?php if ($n['type'] === 'follow'): ?>
            <?= View::partial('partials/follow_button', ['userId' => $n['actor_id'], 'following' => (bool) $n['is_following'], 'size' => 'btn-follow-sm']) ?>
          <?php elseif ($n['thumb_key']): ?>
            <a href="/p/<?= (int) $n['post_id'] ?>" class="js-open-post"><img class="notif-thumb" src="<?= e(Storage::url($n['thumb_key'])) ?>" alt="" loading="lazy"></a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
