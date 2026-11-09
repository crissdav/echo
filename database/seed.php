<?php
/**
 * Echo — datos de demostración.
 *
 * Crea usuarios, seguidores, publicaciones (con imágenes generadas por código y
 * subidas a S3), likes, comentarios y notificaciones, para que el feed no esté vacío.
 *
 * Uso (desde la carpeta del proyecto):
 *     php database/seed.php                 # contraseña demo: Echo2026!
 *     php database/seed.php MiClaveSegura   # contraseña personalizada
 *
 * ¡OJO! Borra todos los datos existentes antes de insertar.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Solo se ejecuta por consola.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database as DB;
use App\Core\Image;
use App\Core\Storage;

mt_srand(2026); // datos reproducibles
$password = $argv[1] ?? 'Echo2026!';
$start = microtime(true);

echo "\n  ECHO · cargando datos de demostración\n";
echo "  Almacenamiento: " . Storage::driver() . (Storage::driver() === 's3' ? ' (bucket ' . env('S3_BUCKET') . ')' : '') . "\n\n";

// ------------------------------------------------------------------
// 0. Limpieza (incluye objetos ya subidos)
// ------------------------------------------------------------------
$oldKeys = array_merge(
    array_column(DB::all('SELECT image_key, thumb_key FROM posts'), 'image_key'),
    array_column(DB::all('SELECT image_key, thumb_key FROM posts'), 'thumb_key')
);
foreach ($oldKeys as $k) {
    try { Storage::delete($k); } catch (Throwable) {}
}
DB::query('DELETE FROM users');      // ON DELETE CASCADE limpia posts, likes, follows...
DB::query('DELETE FROM hashtags');
echo "  ✓ Datos anteriores eliminados (" . count($oldKeys) . " objetos)\n";

// ------------------------------------------------------------------
// 1. Usuarios
// ------------------------------------------------------------------
$users = [
    ['crissdav',        'Leandro Dávila',       'Ing. de Sistemas · FIIS-UNAS ☁️ Desplegando ideas en la nube', 'github.com/crissdav'],
    ['valeria.rios',    'Valeria Ríos',         'Fotografía de naturaleza 📷 Huánuco → el mundo', null],
    ['diego_paredes',   'Diego Paredes',        'Senderista. Si hay catarata, ahí estoy 🌿', null],
    ['lucia.huaman',    'Lucía Huamán',         'Agronomía UNAS 🌱 Café, cacao y buena música', null],
    ['renzo.tello',     'Renzo Tello',          'Backend dev · PHP & MySQL · mate de coca', null],
    ['camila.soto',     'Camila Soto',          'Viajera de fin de semana ✈️', null],
    ['mateo.flores',    'Mateo Flores',         'Atardeceres > todo', null],
    ['andrea.quispe',   'Andrea Quispe',        'Diseño UX/UI ✦ Amo los colores de la selva', null],
    ['sebas.vela',      'Sebastián Vela',       'Ciclismo de montaña 🚵', null],
    ['fer.lozano',      'Fernanda Lozano',      'Bióloga en formación 🦉 Cueva de las Lechuzas fan', null],
    ['joaquin.arevalo', 'Joaquín Arévalo',      'Cloud & DevOps · AWS ☁️', null],
    ['cafe.durmiente',  'Café Durmiente',       'Café de altura de la selva central ☕ Pedidos por DM', null],
];
$hash = password_hash($password, PASSWORD_DEFAULT);
$ids = [];
foreach ($users as $i => [$username, $name, $bio, $web]) {
    $created = date('Y-m-d H:i:s', strtotime('-' . (40 - $i) . ' days'));
    DB::query(
        'INSERT INTO users (username, email, password_hash, full_name, bio, website, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$username, "$username@echo.demo", $hash, $name, $bio, $web, $created]
    );
    $ids[$username] = DB::lastId();
}
echo "  ✓ " . count($ids) . " usuarios creados\n";

// ------------------------------------------------------------------
// 2. Seguidores (crissdav sigue a casi todos para que su feed se vea lleno)
// ------------------------------------------------------------------
$follows = 0;
$names = array_keys($ids);
foreach ($names as $a) {
    foreach ($names as $b) {
        if ($a === $b) continue;
        $p = $a === 'crissdav' ? 0.92 : ($b === 'crissdav' ? 0.75 : 0.45);
        if (mt_rand() / mt_getrandmax() < $p) {
            DB::query('INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, ?)',
                [$ids[$a], $ids[$b], date('Y-m-d H:i:s', strtotime('-' . mt_rand(5, 30) . ' days'))]);
            $follows++;
        }
    }
}
echo "  ✓ $follows relaciones de seguimiento\n";

// ------------------------------------------------------------------
// 3. Generador de imágenes (paisajes de selva y arte "eco")
// ------------------------------------------------------------------
function hex(string $h): array { $h = ltrim($h, '#'); return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))]; }
function mix(array $a, array $b, float $t): array { return [(int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t)]; }
function col(GdImage $im, array $c, int $alpha = 0): int { return imagecolorallocatealpha($im, $c[0], $c[1], $c[2], $alpha); }

function gradient(GdImage $im, array $top, array $bottom, int $h0 = 0, ?int $h1 = null): void
{
    $w = imagesx($im); $h1 ??= imagesy($im);
    for ($y = $h0; $y < $h1; $y++) {
        imageline($im, 0, $y, $w, $y, col($im, mix($top, $bottom, ($y - $h0) / max(1, $h1 - $h0))));
    }
}

/** Cresta de montaña por desplazamiento de punto medio. */
function ridge(int $w, float $base, float $amp, float $rough = 0.55): array
{
    $n = 129;
    $pts = array_fill(0, $n, 0.0);
    $pts[0] = $base + (mt_rand(-100, 100) / 100) * $amp;
    $pts[$n - 1] = $base + (mt_rand(-100, 100) / 100) * $amp;
    $step = $n - 1; $disp = $amp;
    while ($step > 1) {
        $half = intdiv($step, 2);
        for ($i = $half; $i < $n; $i += $step) {
            $pts[$i] = ($pts[$i - $half] + $pts[$i + $half]) / 2 + (mt_rand(-100, 100) / 100) * $disp;
        }
        $step = $half; $disp *= $rough;
    }
    $out = [];
    foreach ($pts as $i => $y) { $out[] = (int) ($i * $w / ($n - 1)); $out[] = (int) $y; }
    return $out;
}

function landscape(int $w, int $h, array $pal): GdImage
{
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    gradient($im, hex($pal['sky'][0]), hex($pal['sky'][1]));

    if (!empty($pal['stars'])) {
        for ($i = 0; $i < 420; $i++) {
            $r = mt_rand(2, 6);
            imagefilledellipse($im, mt_rand(0, $w), mt_rand(0, (int) ($h * 0.55)), $r, $r, col($im, [255, 255, 255], mt_rand(20, 90)));
        }
    }
    // Sol / luna con halo
    $sx = mt_rand((int) ($w * 0.2), (int) ($w * 0.8));
    $sy = (int) ($h * mt_rand(22, 42) / 100);
    $sr = (int) ($w * mt_rand(9, 14) / 100);
    $sun = hex($pal['sun']);
    for ($g = 28; $g >= 1; $g--) { // halo suave: muchas capas casi transparentes
        $d = (int) ($sr * (1 + $g * 0.14));
        imagefilledellipse($im, $sx, $sy, $d, $d, col($im, $sun, 123));
    }
    imagefilledellipse($im, $sx, $sy, $sr, $sr, col($im, $sun));

    // Capas de montañas: de la más lejana (clara) a la más cercana (oscura)
    $layers = count($pal['hills']);
    foreach ($pal['hills'] as $i => $c) {
        $base = $h * (0.48 + $i * (0.42 / $layers));
        $pts = ridge($w, $base, $h * (0.11 - $i * 0.012));
        array_push($pts, $w, $h, 0, $h);
        imagefilledpolygon($im, $pts, col($im, hex($c)));
        if (!empty($pal['mist']) && $i < $layers - 1) { // niebla entre capas
            $band = (int) ($h * 0.07);
            for ($m = 0; $m < $band; $m += 3) { // banda de niebla que aparece y se desvanece
                $y = (int) ($base - $h * 0.01 + $m);
                $a = 127 - (int) (34 * sin(M_PI * $m / $band));
                imagefilledrectangle($im, 0, $y, $w, $y + 2, col($im, hex($pal['mist']), $a));
            }
        }
    }
    // Río serpenteante
    if (!empty($pal['river'])) {
        $rc = hex($pal['river']);
        for ($y = (int) ($h * 0.86); $y < $h; $y += 2) {
            $t = ($y - $h * 0.86) / ($h * 0.14);
            $cx = (int) ($w * 0.5 + sin($t * 3.2) * $w * 0.12);
            $half = (int) (8 + $t * $w * 0.18);
            imageline($im, $cx - $half, $y, $cx + $half, $y, col($im, mix($rc, [255, 255, 255], 0.15 * (1 - $t)), 30));
            imageline($im, $cx - $half, $y + 1, $cx + $half, $y + 1, col($im, $rc, 30));
        }
    }
    return $im;
}

function ripples(int $w, int $h, array $pal): GdImage
{
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    gradient($im, hex($pal['sky'][0]), hex($pal['sky'][1]));
    for ($b = 0; $b < 18; $b++) { // bokeh
        $r = mt_rand((int) ($w * 0.04), (int) ($w * 0.16));
        imagefilledellipse($im, mt_rand(0, $w), mt_rand(0, $h), $r, $r, col($im, [255, 255, 255], mt_rand(105, 120)));
    }
    $cx = (int) ($w * mt_rand(35, 65) / 100); $cy = (int) ($h * mt_rand(40, 60) / 100);
    imagesetthickness($im, max(4, intdiv($w, 220)));
    for ($r = 1; $r <= 14; $r++) { // ondas de "eco"
        $d = (int) ($w * 0.08 * $r);
        imageellipse($im, $cx, $cy, $d, $d, col($im, [255, 255, 255], min(120, 18 + $r * 7)));
    }
    imagefilledellipse($im, $cx, $cy, (int) ($w * 0.07), (int) ($w * 0.07), col($im, [255, 255, 255], 10));
    return $im;
}

$palettes = [
    'amanecer'  => ['sky' => ['#ffb38a', '#ffe8c8'], 'sun' => '#fff3d6', 'hills' => ['#a7a39a', '#6f8a6a', '#3f6a4c', '#1f4532'], 'mist' => '#ffffff', 'river' => '#9ec9d6'],
    'selva'     => ['sky' => ['#8fd3f4', '#e0f7ef'], 'sun' => '#fffbe6', 'hills' => ['#8cc0a0', '#4f9a6e', '#2e7650', '#14492f'], 'mist' => '#ffffff', 'river' => '#5fb0c9'],
    'atardecer' => ['sky' => ['#5b2a86', '#ff8c5a'], 'sun' => '#ffd27a', 'hills' => ['#8a4f7d', '#5e3a6e', '#3b2752', '#1d1430']],
    'noche'     => ['sky' => ['#0b1026', '#2b3a67'], 'sun' => '#f4f1de', 'hills' => ['#2a3558', '#1d2744', '#121a30', '#090e1c'], 'stars' => true],
    'niebla'    => ['sky' => ['#c9d6df', '#f1f4f6'], 'sun' => '#ffffff', 'hills' => ['#a9b8b0', '#7d9488', '#536f62', '#2f4a3f'], 'mist' => '#ffffff', 'river' => '#b8d4dc'],
    'tropical'  => ['sky' => ['#11998e', '#a8f0c6'], 'sun' => '#fffde4', 'hills' => ['#5cb88a', '#2f8f6b', '#1a6b52', '#0b4434'], 'river' => '#7fd6e0'],
];
$artPalettes = [
    ['sky' => ['#6d28d9', '#06b6d4']], ['sky' => ['#ec4899', '#8b5cf6']], ['sky' => ['#0ea5e9', '#22c55e']],
    ['sky' => ['#f97316', '#db2777']], ['sky' => ['#1e1b4b', '#7c3aed']],
];

// ------------------------------------------------------------------
// 4. Publicaciones
// ------------------------------------------------------------------
$posts = [
    ['valeria.rios',    'amanecer',  'Tingo María, Huánuco',      'Amanecer sobre la Bella Durmiente. Valió cada minuto de madrugada 🌄 #TingoMaría #BellaDurmiente #Amanecer'],
    ['diego_paredes',   'selva',     'Catarata Santa Carmen',     'Ruta de 3 horas, cero arrepentimientos 💧 #Selva #Naturaleza #Huánuco'],
    ['crissdav',        'art',       'FIIS · UNAS',               'Primer post en Echo, desplegado en AWS con EC2 + RDS + S3 ☁️ Cada foto que ves vive en un bucket privado. #AWS #Nube #FIIS #UNAS'],
    ['lucia.huaman',    'niebla',    'Parque Nacional Tingo María','Neblina de la mañana entre los cerros 🌫️ #TingoMaría #Naturaleza'],
    ['mateo.flores',    'atardecer', 'Mirador de Jacintillo',     'Otro atardecer para la colección 🧡 #Atardecer #TingoMaría'],
    ['fer.lozano',      'noche',     'Cueva de las Lechuzas',     'Noche de campo con el grupo de biología. Se escuchaban los guácharos 🦉✨ #CuevaDeLasLechuzas #Naturaleza'],
    ['andrea.quispe',   'art',       null,                        'Explorando paletas para un nuevo proyecto. ¿Violeta o turquesa? 🎨 #Diseño #UX'],
    ['cafe.durmiente',  'tropical',  'Tingo María, Huánuco',      'Así se ven nuestras parcelas después de la lluvia ☕🌿 Café de altura, 100% local. #Café #TingoMaría #Selva'],
    ['camila.soto',     'selva',     'Laguna de los Milagros',    'Fin de semana perfecto 💚 @valeria.rios gracias por la ruta #Viajes #Selva'],
    ['joaquin.arevalo', 'art',       'us-east-2 (Ohio)',          'Grupos de seguridad bien configurados = noches tranquilas 😌 #AWS #DevOps #Nube'],
    ['sebas.vela',      'amanecer',  'Carretera a Monzón',        '42 km de subida, la vista lo paga todo 🚵 #Ciclismo #Huánuco'],
    ['renzo.tello',     'noche',     null,                        'Deploy un domingo a medianoche, ¿qué podría salir mal? 😅 #PHP #MySQL #FIIS'],
    ['valeria.rios',    'niebla',    'Velo de las Ninfas',        'Lugares que parecen de otro planeta 🌿 #TingoMaría #Naturaleza #Huánuco'],
    ['lucia.huaman',    'tropical',  'Fundo UNAS',                'Práctica de campo en el fundo de la universidad 🌱 #UNAS #Agronomía'],
    ['mateo.flores',    'atardecer', 'Río Huallaga',              'El Huallaga en hora dorada ✨ #Atardecer #Selva'],
    ['crissdav',        'selva',     'Tingo María, Huánuco',      'Desconectando un rato del código 🌳 #TingoMaría #BellaDurmiente'],
    ['diego_paredes',   'amanecer',  'Bella Durmiente',           'Cumbre lograda antes del amanecer ⛰️ @diego_paredes vs la montaña: 1-0 #BellaDurmiente #Amanecer'],
    ['andrea.quispe',   'atardecer', 'Tingo María, Huánuco',      'Los colores de este cielo son mi nueva paleta favorita 💜🧡 #Atardecer #Diseño'],
    ['cafe.durmiente',  'art',       null,                        'Nueva temporada, nuevo tueste ☕ Escríbenos para pedidos. #Café #TingoMaría'],
    ['fer.lozano',      'selva',     'Parque Nacional Tingo María','Inventario de aves completado: 23 especies 🐦 #Naturaleza #CuevaDeLasLechuzas'],
    ['camila.soto',     'niebla',    'Huánuco',                   'Días nublados también tienen lo suyo ☁️ #Viajes #Huánuco'],
    ['joaquin.arevalo', 'noche',     null,                        'Monitoreando métricas de CloudWatch con café en mano ☕📈 #AWS #Nube'],
    ['crissdav',        'art',       'FIIS · UNAS',               'Echo ya tiene likes, comentarios, hashtags y notificaciones en tiempo real ⚡ Todo en una RDS privada. #AWS #FIIS #Nube'],
    ['valeria.rios',    'atardecer', 'Tingo María, Huánuco',      'Última luz del día sobre la ciudad 🌇 #TingoMaría #Atardecer'],
];
$sizes = [[1080, 1350], [1080, 1080], [1350, 1080], [1080, 1350]];
$total = count($posts);
$postIds = [];
foreach ($posts as $i => [$username, $style, $location, $caption]) {
    [$w, $h] = $sizes[$i % count($sizes)];
    $gd = $style === 'art'
        ? ripples($w * 2, $h * 2, $artPalettes[$i % count($artPalettes)])
        : landscape($w * 2, $h * 2, $palettes[$style]);
    $img = Image::fromGd($gd);
    $imageKey = Image::key('posts');
    $thumbKey = str_replace('.jpg', '_t.jpg', $imageKey);
    Storage::put($imageKey, $img['full']);
    Storage::put($thumbKey, $img['thumb']);
    @unlink($img['full']); @unlink($img['thumb']);

    // Fechas ascendentes: las más recientes al final (el feed ordena por id).
    $hoursAgo = (int) (($total - $i) * 13 + mt_rand(0, 8));
    $created = date('Y-m-d H:i:s', time() - $hoursAgo * 3600);
    DB::query(
        'INSERT INTO posts (user_id, image_key, thumb_key, width, height, caption, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$ids[$username], $imageKey, $thumbKey, $img['width'], $img['height'], $caption, $location, $created]
    );
    $pid = DB::lastId();
    $postIds[$pid] = [$ids[$username], $created];
    foreach (extract_hashtags($caption) as $tag) {
        DB::query('INSERT INTO hashtags (tag) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)', [$tag]);
        DB::query('INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)', [$pid, DB::lastId()]);
    }
    printf("\r  ✓ Publicaciones: %d/%d (imágenes subidas a %s)", $i + 1, $total, Storage::driver());
}
echo "\n";

// ------------------------------------------------------------------
// 5. Likes, comentarios y notificaciones
// ------------------------------------------------------------------
$commentPool = [
    '¡Qué foto! 😍', 'Increíble lugar 🔥', 'Necesito ir ya mismo', 'Los colores 🤩', '¿Dónde queda exactamente?',
    'Brutal 👏', 'Esto es Tingo María de verdad 💚', 'Me encanta', 'La próxima voy contigo', 'Wallpaper oficial',
    '¿Con qué cámara la tomaste?', 'Qué paz transmite', 'Top 🙌', 'Uff, qué vista', '+1 a esto',
    'Hermoso 🌿', 'Se ve espectacular', 'Guardado para mi próximo viaje', 'Arte puro ✨', 'Jajaja tal cual',
];
$likes = 0; $comments = 0;
$userIds = array_values($ids);
foreach ($postIds as $pid => [$owner, $created]) {
    $base = strtotime($created);
    foreach ($userIds as $uid) {
        if ($uid !== $owner && mt_rand(1, 100) <= 62) {
            $at = date('Y-m-d H:i:s', min(time() - 60, $base + mt_rand(300, 40000)));
            DB::query('INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, ?)', [$uid, $pid, $at]);
            DB::query("INSERT INTO notifications (user_id, actor_id, type, post_id, is_read, created_at) VALUES (?, ?, 'like', ?, ?, ?)",
                [$owner, $uid, $pid, (int) (mt_rand(1, 100) <= 70), $at]);
            $likes++;
        }
    }
    $n = mt_rand(1, 4);
    for ($c = 0; $c < $n; $c++) {
        $uid = $userIds[array_rand($userIds)];
        if ($uid === $owner) continue;
        $at = date('Y-m-d H:i:s', min(time() - 30, $base + mt_rand(600, 50000)));
        DB::query('INSERT INTO comments (post_id, user_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$pid, $uid, $commentPool[array_rand($commentPool)], $at]);
        DB::query("INSERT INTO notifications (user_id, actor_id, type, post_id, is_read, created_at) VALUES (?, ?, 'comment', ?, ?, ?)",
            [$owner, $uid, $pid, (int) (mt_rand(1, 100) <= 70), $at]);
        $comments++;
    }
}
// Notificaciones de nuevos seguidores
DB::query(
    "INSERT INTO notifications (user_id, actor_id, type, post_id, is_read, created_at)
     SELECT following_id, follower_id, 'follow', NULL, 1, created_at FROM follows"
);
echo "  ✓ $likes me gusta y $comments comentarios\n";

$stats = DB::one('SELECT * FROM v_estadisticas');
printf("\n  Listo en %.1f s → %s\n", microtime(true) - $start, json_encode($stats, JSON_UNESCAPED_UNICODE));
echo "  Inicia sesión con:  crissdav / $password  (o cualquier usuario demo)\n\n";
