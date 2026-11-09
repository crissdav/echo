<?php
use App\Core\Auth;

/** @var array $c  @var int $postOwner  @var bool $full */
$canDelete = (int) $c['user_id'] === Auth::id() || (int) ($postOwner ?? 0) === Auth::id();
?>
<?php if (!empty($full)): ?>
  <div class="comment" data-comment-id="<?= (int) $c['id'] ?>">
    <a href="/u/<?= e($c['username']) ?>"><?= avatar($c, 32) ?></a>
    <div class="comment-text">
      <a href="/u/<?= e($c['username']) ?>" class="uname"><?= e($c['username']) ?></a> <?= render_caption($c['body']) ?>
      <div class="comment-meta">
        <span><?= time_ago($c['created_at']) ?></span>
        <?php if ($canDelete): ?><button class="link-btn js-del-comment" data-id="<?= (int) $c['id'] ?>">Eliminar</button><?php endif; ?>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="c-line"><a href="/u/<?= e($c['username']) ?>" class="uname"><?= e($c['username']) ?></a> <?= render_caption($c['body']) ?></div>
<?php endif; ?>
