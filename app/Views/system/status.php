<?php
/** @var array $info */
$ec2 = $info['ec2']; $db = $info['db']; $st = $info['storage']; $stats = $info['stats'];
$ok = fn (bool $b) => $b ? '<span class="state ok"><i class="bi bi-check-circle-fill"></i> Operativo</span>' : '<span class="state bad"><i class="bi bi-x-circle-fill"></i> Con fallas</span>';
?>
<div class="page-wide">
  <header class="status-hero">
    <div>
      <h1>Estado del sistema</h1>
      <p>Infraestructura de Echo en Amazon Web Services · región <strong>us-east-2 (Ohio)</strong></p>
    </div>
    <?php $all = $db['ok'] && $st['ok']; ?>
    <div class="status-overall <?= $all ? 'ok' : 'bad' ?>">
      <span class="pulse"></span><?= $all ? 'Todos los servicios operativos' : 'Servicio degradado' ?>
    </div>
  </header>

  <!-- Diagrama de flujo simple -->
  <div class="arch">
    <div class="arch-node"><i class="bi bi-phone"></i><span>Usuario</span><small>Navegador</small></div>
    <div class="arch-arrow"><span>HTTP :80</span></div>
    <div class="arch-node primary"><i class="bi bi-hdd-rack"></i><span>Amazon EC2</span><small>Apache + PHP <?= e(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) ?></small></div>
    <div class="arch-split">
      <div class="arch-branch"><div class="arch-arrow"><span>MySQL :3306 · TLS</span></div><div class="arch-node db"><i class="bi bi-database"></i><span>Amazon RDS</span><small>Subred privada</small></div></div>
      <div class="arch-branch"><div class="arch-arrow"><span>HTTPS · rol IAM</span></div><div class="arch-node s3"><i class="bi bi-bucket"></i><span>Amazon S3</span><small>Bucket privado</small></div></div>
    </div>
  </div>

  <div class="status-grid">
    <section class="status-card">
      <header><span class="svc svc-ec2"><i class="bi bi-hdd-rack"></i></span><div><h3>Cómputo</h3><small>Amazon EC2</small></div><?= $ok(true) ?></header>
      <dl>
        <?php if ($ec2['available'] ?? false): ?>
          <dt>ID de instancia</dt><dd><code><?= e($ec2['instance_id']) ?></code></dd>
          <dt>Tipo</dt><dd><?= e($ec2['instance_type']) ?></dd>
          <dt>Zona de disponibilidad</dt><dd><?= e($ec2['availability_zone']) ?></dd>
          <dt>IP privada</dt><dd><?= e($ec2['private_ip']) ?></dd>
          <dt>Rol IAM</dt><dd><?= e($ec2['iam_role'] ?: 'Sin rol') ?></dd>
        <?php else: ?>
          <dt>Entorno</dt><dd>Local (fuera de EC2)</dd>
        <?php endif; ?>
        <dt>Servidor web</dt><dd><?= e($info['app']['server']) ?></dd>
        <dt>PHP</dt><dd><?= e($info['app']['php']) ?></dd>
      </dl>
    </section>

    <section class="status-card">
      <header><span class="svc svc-rds"><i class="bi bi-database"></i></span><div><h3>Base de datos</h3><small>Amazon RDS</small></div><?= $ok($db['ok']) ?></header>
      <dl>
        <?php if ($db['ok']): ?>
          <dt>Motor</dt><dd><?= e(stripos((string) $db['version'], 'mariadb') !== false ? 'MariaDB' : 'MySQL') ?> <?= e(strtok((string) $db['version'], '-')) ?></dd>
          <dt>Endpoint</dt><dd><code><?= e($db['host']) ?></code></dd>
          <dt>Base / usuario</dt><dd><?= e($db['name']) ?> / <?= e($db['user']) ?></dd>
          <dt>Cifrado en tránsito</dt><dd><?= $db['tls'] ? '<span class="tag-ok"><i class="bi bi-lock-fill"></i> TLS · ' . e($db['tls']) . '</span>' : '<span class="tag-warn">Sin TLS</span>' ?></dd>
          <dt>Latencia</dt><dd><?= e($db['latency_ms']) ?> ms</dd>
        <?php else: ?>
          <dt>Error</dt><dd><?= e($db['error'] ?? 'Desconocido') ?></dd>
        <?php endif; ?>
      </dl>
    </section>

    <section class="status-card">
      <header><span class="svc svc-s3"><i class="bi bi-bucket"></i></span><div><h3>Almacenamiento</h3><small>Amazon S3</small></div><?= $ok($st['ok']) ?></header>
      <dl>
        <dt>Driver</dt><dd><?= e(strtoupper($st['driver'])) ?></dd>
        <dt>Bucket</dt><dd><code><?= e($st['bucket'] ?? '—') ?></code></dd>
        <?php if (!empty($st['region'])): ?><dt>Región</dt><dd><?= e($st['region']) ?></dd><?php endif; ?>
        <dt>Acceso</dt><dd><?= $st['driver'] === 's3' ? 'Privado · URLs prefirmadas' : 'Disco local' ?></dd>
        <?php if (isset($st['latency_ms'])): ?><dt>Latencia</dt><dd><?= e($st['latency_ms']) ?> ms</dd><?php endif; ?>
      </dl>
    </section>
  </div>

  <?php if ($stats): ?>
    <h2 class="section-title">Datos almacenados en RDS</h2>
    <div class="kpis">
      <?php foreach (['usuarios' => 'people', 'publicaciones' => 'images', 'me_gusta' => 'heart', 'comentarios' => 'chat', 'relaciones_seguimiento' => 'person-check', 'hashtags' => 'hash'] as $k => $icon): ?>
        <div class="kpi"><i class="bi bi-<?= $icon ?>"></i><strong><?= short_number((int) $stats[$k]) ?></strong><span><?= ucfirst(str_replace('_', ' ', $k)) ?></span></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <p class="muted small mt-3">Consultado: <?= e($info['app']['time']) ?> (hora de Lima) · JSON en <a href="/health">/health</a></p>
</div>
