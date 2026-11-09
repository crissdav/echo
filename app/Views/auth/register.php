<h2 class="auth-title">Crea tu cuenta</h2>
<p class="auth-sub">Regístrate para compartir fotos con tu comunidad.</p>

<form method="post" action="/register" class="auth-form" novalidate>
  <?= csrf_field() ?>
  <div class="form-floating mb-2">
    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nombre" value="<?= old('full_name') ?>" maxlength="60" required autofocus>
    <label for="full_name">Nombre completo</label>
  </div>
  <div class="form-floating mb-2">
    <input type="text" class="form-control" id="username" name="username" placeholder="usuario" value="<?= old('username') ?>" maxlength="30" pattern="[a-z0-9_.]{3,30}" autocomplete="username" required>
    <label for="username">Nombre de usuario</label>
  </div>
  <div class="form-floating mb-2">
    <input type="email" class="form-control" id="email" name="email" placeholder="correo" value="<?= old('email') ?>" autocomplete="email" required>
    <label for="email">Correo electrónico</label>
  </div>
  <div class="form-floating mb-1 position-relative">
    <input type="password" class="form-control" id="password" name="password" placeholder="contraseña" minlength="8" autocomplete="new-password" required>
    <label for="password">Contraseña</label>
    <button type="button" class="pw-toggle js-pw-toggle" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
  </div>
  <div class="pw-meter mb-3"><span class="js-pw-meter"></span></div>
  <button class="btn btn-echo w-100 py-2">Registrarte</button>
  <p class="legal">Tu contraseña se guarda cifrada con bcrypt. Nunca en texto plano.</p>
</form>

<p class="auth-switch">¿Ya tienes cuenta? <a href="/login">Inicia sesión</a></p>
