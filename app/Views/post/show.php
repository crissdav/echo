<?php
use App\Core\Auth;
use App\Core\Storage;
use App\Core\View;

$mine = (int) $post['user_id'] === Auth::id();
$likes = (int) $post['likes_count'];
?>
<div class="page-narrow-xl">
  <a href="javascript:history.back()" class="back-link"><i class="bi bi-arrow-left"></i> Volver</a>
  <article class="post post-detail" data-post-id="<?= (int) $post['id'] ?>">
    <div class="post-media js-dbl-like">
      <img src="<?= e(Storage::url($post['image_key'])) ?>" alt="Foto de <?= e($post['username']) ?>">
      <span class="heart-burst"><i class="bi bi-heart-fill"></i></span>
    </div>

    <div class="detail-side">
      <header class="post-head">
        <a href="/u/<?= e($post['username']) ?>" class="ring-sm"><?= avatar($post, 34) ?></a>
        <div class="post-meta">
          <a href="/u/<?= e($post['username']) ?>" class="uname"><?= e($post['username']) ?></a>
          <?php if ($post['location']): ?><div class="loc"><?= e($post['location']) ?></div><?php endif; ?>
        </div>
        <?php if ($mine): ?>
          <form method="post" action="/p/<?= (int) $post['id'] ?>/delete" class="ms-auto js-confirm" data-confirm="¿Eliminar esta publicación? No se puede deshacer.">
            <?= csrf_field() ?><button class="icon-btn text-danger" aria-label="Eliminar"><i class="bi bi-trash3"></i></button>
          </form>
        <?php endif; ?>
      </header>

      <div class="detail-comments js-comments">
        <?php if ($post['caption']): ?>
          <div class="comment">
            <a href="/u/<?= e($post['username']) ?>"><?= avatar($post, 32) ?></a>
            <div class="comment-text">
              <a href="/u/<?= e($post['username']) ?>" class="uname"><?= e($post['username']) ?></a> <?= render_caption($post['caption']) ?>
              <div class="comment-meta"><span><?= time_ago($post['created_at']) ?></span></div>
            </div>
          </div>
        <?php endif; ?>
        <?php foreach ($comments as $c): ?>
          <?= View::partial('partials/comment', ['c' => $c, 'postOwner' => (int) $post['user_id'], 'full' => true]) ?>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
          <div class="no-comments js-no-comments"><strong>Aún no hay comentarios.</strong><span>Inicia la conversación.</span></div>
        <?php endif; ?>
      </div>

      <div class="detail-foot">
        <div class="post-actions">
          <button class="icon-btn js-like <?= $post['liked'] ? 'liked' : '' ?>" aria-label="Me gusta">
            <i class="bi <?= $post['liked'] ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
          </button>
          <button class="icon-btn js-focus-comment" aria-label="Comentar"><i class="bi bi-chat"></i></button>
          <button class="icon-btn js-share" data-url="/p/<?= (int) $post['id'] ?>" aria-label="Compartir"><i class="bi bi-send"></i></button>
        </div>
        <div class="post-body pt-0">
          <div class="likes"><span class="js-like-count"><?= short_number($likes) ?></span> Me gusta</div>
          <div class="muted xsmall text-uppercase"><?= time_ago($post['created_at']) ?></div>
        </div>
        <form class="comment-form js-comment-form border-top" data-post-id="<?= (int) $post['id'] ?>" data-full="1">
          <input type="text" name="body" maxlength="500" placeholder="Agrega un comentario…" autocomplete="off" aria-label="Comentario">
          <button type="submit" disabled>Publicar</button>
        </form>
      </div>
    </div>
  </article>
</div>
