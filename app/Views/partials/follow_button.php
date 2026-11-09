<?php /** @var int $userId @var bool $following @var string $size */ ?>
<button class="btn-follow js-follow <?= $following ? 'is-following' : '' ?> <?= $size ?? '' ?>" data-user-id="<?= (int) $userId ?>">
  <?= $following ? 'Siguiendo' : 'Seguir' ?>
</button>
