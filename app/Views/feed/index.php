<?php
use App\Core\Auth;
use App\Core\View;

$me = Auth::user();
?>
<div class="feed-layout">
  <section class="feed-col">

    <?php if ($active): ?>
      <div class="stories" aria-label="Cuentas que sigues">
        <?php foreach ($active as $u): ?>
          <a href="/u/<?= e($u['username']) ?>" class="story">
            <span class="ring"><?= avatar($u, 58) ?></span>
            <span class="story-name"><?= e($u['username']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$posts): ?>
      <div class="empty">
        <div class="empty-icon"><i class="bi bi-broadcast"></i></div>
        <h3>Tu feed está en silencio</h3>
        <p>Sigue a otras cuentas o publica tu primera foto para empezar a hacer eco.</p>
        <div class="d-flex gap-2 justify-content-center">
          <a href="/explore" class="btn btn-echo">Explorar cuentas</a>
          <a href="/new" class="btn btn-light border">Publicar</a>
        </div>
      </div>
    <?php endif; ?>

    <div class="posts js-feed">
      <?php foreach ($posts as $post): ?>
        <?= View::partial('partials/post_card', ['post' => $post]) ?>
      <?php endforeach; ?>
    </div>

    <?php if ($nextCursor): ?>
      <div class="feed-sentinel js-sentinel" data-next="<?= (int) $nextCursor ?>"><div class="spinner"></div></div>
    <?php elseif ($posts): ?>
      <div class="feed-end"><i class="bi bi-check2-circle"></i><strong>Estás al día</strong><span>Ya viste todas las publicaciones recientes.</span></div>
    <?php endif; ?>
  </section>

  <aside class="feed-side">
    <div class="me-card">
      <a href="/u/<?= e($me['username']) ?>"><?= avatar($me, 46) ?></a>
      <div class="flex-grow-1 min-w-0">
        <a href="/u/<?= e($me['username']) ?>" class="uname d-block"><?= e($me['username']) ?></a>
        <div class="muted small text-truncate"><?= e($me['full_name']) ?></div>
      </div>
      <a href="/settings" class="link-strong small">Editar</a>
    </div>

    <?php if ($suggestions): ?>
      <div class="side-title"><span>Sugerencias para ti</span><a href="/explore">Ver todo</a></div>
      <?php foreach ($suggestions as $s): ?>
        <div class="suggest">
          <a href="/u/<?= e($s['username']) ?>"><?= avatar($s, 38) ?></a>
          <div class="flex-grow-1 min-w-0">
            <a href="/u/<?= e($s['username']) ?>" class="uname d-block text-truncate"><?= e($s['username']) ?></a>
            <div class="muted xsmall text-truncate">
              <?= (int) $s['mutuals'] > 0 ? 'Le siguen ' . (int) $s['mutuals'] . ' cuentas que sigues' : short_number((int) $s['followers_count']) . ' seguidores' ?>
            </div>
          </div>
          <?= View::partial('partials/follow_button', ['userId' => $s['id'], 'following' => false, 'size' => 'btn-follow-link']) ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <footer class="side-foot">
      <div class="aws-chip"><i class="bi bi-cloud-fill"></i> Desplegado en AWS · EC2 · RDS · S3</div>
      <a href="/status">Estado del sistema</a> · <a href="/explore">Explorar</a> · <a href="/settings">Configuración</a>
      <div class="mt-2">© <?= date('Y') ?> ECHO · FIIS – UNAS</div>
    </footer>
  </aside>
</div>
