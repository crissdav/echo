<?php
use App\Core\Auth;
use App\Core\Storage;

/** @var array $post */
$mine = (int) $post['user_id'] === Auth::id();
$likes = (int) $post['likes_count'];
$comments = (int) $post['comments_count'];
?>
<article class="post" data-post-id="<?= (int) $post['id'] ?>">
  <header class="post-head">
    <a href="/u/<?= e($post['username']) ?>" class="ring-sm"><?= avatar($post, 34) ?></a>
    <div class="post-meta">
      <div>
        <a href="/u/<?= e($post['username']) ?>" class="uname"><?= e($post['username']) ?></a>
        <span class="dotsep">•</span>
        <a href="/p/<?= (int) $post['id'] ?>" class="muted small" title="<?= e($post['created_at']) ?>"><?= time_ago($post['created_at']) ?></a>
      </div>
      <?php if ($post['location']): ?><div class="loc"><?= e($post['location']) ?></div><?php endif; ?>
    </div>
    <div class="dropdown ms-auto">
      <button class="icon-btn" data-bs-toggle="dropdown" aria-label="Opciones"><i class="bi bi-three-dots"></i></button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
        <li><a class="dropdown-item rounded-3" href="/p/<?= (int) $post['id'] ?>"><i class="bi bi-box-arrow-up-right me-2"></i>Ir a la publicación</a></li>
        <li><button class="dropdown-item rounded-3 js-share" data-url="/p/<?= (int) $post['id'] ?>"><i class="bi bi-link-45deg me-2"></i>Copiar enlace</button></li>
        <?php if ($mine): ?>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="post" action="/p/<?= (int) $post['id'] ?>/delete" class="js-confirm" data-confirm-title="¿Eliminar publicación?" data-confirm="Se borrará la foto y todos sus comentarios. No se puede deshacer." data-confirm-ok="Eliminar">
              <?= csrf_field() ?><button class="dropdown-item rounded-3 text-danger"><i class="bi bi-trash3 me-2"></i>Eliminar</button>
            </form>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </header>

  <div class="post-media js-dbl-like" style="aspect-ratio: <?= (int) $post['width'] ?> / <?= (int) $post['height'] ?>">
    <img src="<?= e(Storage::url($post['image_key'])) ?>" alt="Foto de <?= e($post['username']) ?>" loading="lazy" decoding="async">
    <span class="heart-burst"><i class="bi bi-heart-fill"></i></span>
  </div>

  <div class="post-actions">
    <button class="icon-btn js-like <?= $post['liked'] ? 'liked' : '' ?>" aria-label="Me gusta">
      <i class="bi <?= $post['liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
    </button>
    <a class="icon-btn js-open-post" href="/p/<?= (int) $post['id'] ?>" aria-label="Comentar"><i class="bi bi-chat"></i></a>
    <button class="icon-btn js-share" data-url="/p/<?= (int) $post['id'] ?>" aria-label="Compartir"><i class="bi bi-send"></i></button>
  </div>

  <div class="post-body">
    <div class="likes"><span class="js-like-count"><?= short_number($likes) ?></span> <span class="js-like-word"><?= $likes === 1 ? 'Me gusta' : 'Me gusta' ?></span></div>
    <?php if ($post['caption']): ?>
      <div class="caption clamp js-clamp">
        <a href="/u/<?= e($post['username']) ?>" class="uname"><?= e($post['username']) ?></a>
        <?= render_caption($post['caption']) ?>
      </div>
    <?php endif; ?>
    <?php if ($comments > 2): ?>
      <a href="/p/<?= (int) $post['id'] ?>" class="view-all js-open-post">Ver los <?= $comments ?> comentarios</a>
    <?php endif; ?>
    <div class="comment-preview js-comments">
      <?php foreach ($post['preview_comments'] ?? [] as $c): ?>
        <div class="c-line"><a href="/u/<?= e($c['username']) ?>" class="uname"><?= e($c['username']) ?></a> <?= render_caption($c['body']) ?></div>
      <?php endforeach; ?>
    </div>
    <form class="comment-form js-comment-form" data-post-id="<?= (int) $post['id'] ?>">
      <input type="text" name="body" maxlength="500" placeholder="Agrega un comentario…" autocomplete="off" aria-label="Comentario">
      <button type="submit" disabled>Publicar</button>
    </form>
  </div>
</article>
