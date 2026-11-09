<?php
use App\Core\Auth;

$me = Auth::user();
?>
<div class="page-narrow">
  <h1 class="page-title">Crear publicación</h1>
  <form method="post" action="/new" enctype="multipart/form-data" class="create-card js-create">
    <?= csrf_field() ?>
    <label class="dropzone js-dropzone">
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="js-file" required hidden>
      <div class="dz-empty">
        <div class="dz-icon"><i class="bi bi-images"></i></div>
        <strong>Arrastra tu foto aquí</strong>
        <span>JPG, PNG o WEBP · máx. 8 MB</span>
        <span class="btn btn-echo btn-sm mt-2">Seleccionar desde el equipo</span>
      </div>
      <img class="dz-preview" alt="Vista previa">
      <span class="dz-change"><i class="bi bi-arrow-repeat"></i> Cambiar</span>
    </label>

    <div class="create-fields">
      <div class="d-flex align-items-center gap-2 mb-3">
        <?= avatar($me, 30) ?><span class="uname"><?= e($me['username']) ?></span>
      </div>
      <textarea name="caption" class="form-control caption-input js-caption" rows="5" maxlength="2200" placeholder="Escribe una descripción… usa #hashtags y @menciones"></textarea>
      <div class="text-end muted xsmall mt-1"><span class="js-count">0</span>/2200</div>
      <div class="input-icon mt-2">
        <i class="bi bi-geo-alt"></i>
        <input type="text" name="location" class="form-control" maxlength="80" placeholder="Agregar ubicación (ej. Tingo María, Huánuco)">
      </div>
      <div class="s3-note"><i class="bi bi-shield-lock"></i> La imagen se optimiza, se eliminan sus metadatos (GPS) y se guarda en un bucket privado de Amazon S3.</div>
      <button class="btn btn-echo w-100 py-2 mt-3 js-submit" disabled>
        <span class="js-submit-label">Compartir</span>
      </button>
    </div>
  </form>
</div>
