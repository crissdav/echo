<h2 class="auth-title">Inicia sesión</h2>
<p class="auth-sub">Vuelve a ver lo que está haciendo eco hoy.</p>

<form method="post" action="/login" class="auth-form" novalidate>
  <?= csrf_field() ?>
  <div class="form-floating mb-2">
    <input type="text" class="form-control" id="login" name="login" placeholder="usuario" value="<?= old('login') ?>" autocomplete="username" required autofocus>
    <label for="login">Usuario o correo electrónico</label>
  </div>
  <div class="form-floating mb-3 position-relative">
    <input type="password" class="form-control" id="password" name="password" placeholder="contraseña" autocomplete="current-password" required>
    <label for="password">Contraseña</label>
    <button type="button" class="pw-toggle js-pw-toggle" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
  </div>
  <button class="btn btn-echo w-100 py-2">Entrar</button>
</form>

<?php if (env('SHOW_DEMO_LOGIN') === 'true'): ?>
  <div class="demo-hint">
    <i class="bi bi-person-badge"></i>
    <div><strong>Cuenta de demostración</strong><br>Usuario <code>crissdav</code> · Contraseña <code><?= e(env('DEMO_PASSWORD', 'Echo2026!')) ?></code></div>
  </div>
<?php endif; ?>

<p class="auth-switch">¿No tienes cuenta? <a href="/register">Regístrate</a></p>
