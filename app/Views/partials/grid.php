<?php
use App\Core\Storage;

/** @var array $posts  @var bool $mosaic */
?>
<div class="grid <?= !empty($mosaic) ? 'grid-mosaic' : '' ?>">
  <?php foreach ($posts as $p): ?>
    <a href="/p/<?= (int) $p['id'] ?>" class="tile">
      <img src="<?= e(Storage::url($p['thumb_key'])) ?>" alt="" loading="lazy" decoding="async">
      <span class="tile-over">
        <span><i class="bi bi-heart-fill"></i> <?= short_number((int) $p['likes_count']) ?></span>
        <span><i class="bi bi-chat-fill"></i> <?= short_number((int) $p['comments_count']) ?></span>
      </span>
    </a>
  <?php endforeach; ?>
</div>
