<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Env;
use App\Core\Storage;

function env(string $key, ?string $default = null): ?string
{
    return Env::get($key, $default);
}

/** Escapa HTML (previene XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : '1';
    return '/assets/' . $path . '?v=' . $v;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Mensajes flash de una sola lectura. */
function flash(string $type, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $msg;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function remember_input(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function forget_input(): void
{
    unset($_SESSION['_old']);
}

/** "hace 5 min", "hace 3 h", "8 nov" */
function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)        return 'ahora';
    if ($diff < 3600)      return 'hace ' . intdiv($diff, 60) . ' min';
    if ($diff < 86400)     return 'hace ' . intdiv($diff, 3600) . ' h';
    if ($diff < 604800)    return 'hace ' . intdiv($diff, 86400) . ' d';
    if ($diff < 2419200)   return 'hace ' . intdiv($diff, 604800) . ' sem';
    $months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $label = date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
    return date('Y', $ts) === date('Y') ? $label : $label . ' ' . date('Y', $ts);
}

/** 1234 -> "1,2 mil" */
function short_number(int $n): string
{
    if ($n >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1, ',', ''), '0'), ',') . ' M';
    if ($n >= 10_000)    return rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',') . ' mil';
    return number_format($n, 0, ',', '.');
}

/** Convierte #hashtags y @menciones en enlaces (sobre texto ya escapado). */
function render_caption(string $text): string
{
    $html = nl2br(e($text));
    $html = preg_replace('/(^|[\s>])#([\p{L}\p{N}_]{1,50})/u', '$1<a class="tag-link" href="/tag/$2">#$2</a>', $html);
    $html = preg_replace('/(^|[\s>])@([a-zA-Z0-9_.]{3,30})/u', '$1<a class="mention-link" href="/u/$2">@$2</a>', $html);
    return $html;
}

/** Extrae hashtags únicos en minúsculas. */
function extract_hashtags(string $text): array
{
    preg_match_all('/#([\p{L}\p{N}_]{1,50})/u', $text, $m);
    return array_values(array_unique(array_map(static fn ($t) => mb_strtolower($t), $m[1])));
}

/** Avatar: foto en S3 o círculo con iniciales y degradado. */
function avatar(array $user, int $size = 32, string $class = ''): string
{
    $name = $user['full_name'] ?: $user['username'];
    if (!empty($user['avatar_key'])) {
        return sprintf(
            '<img class="avatar %s" src="%s" alt="%s" width="%d" height="%d" loading="lazy">',
            e($class), e(Storage::url($user['avatar_key'])), e($name), $size, $size
        );
    }
    $words = preg_split('/\s+/', trim($name));
    $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
    $hue = crc32((string) $user['username']) % 360;
    return sprintf(
        '<span class="avatar avatar-initials %s" style="--s:%dpx;--h:%d" aria-label="%s">%s</span>',
        e($class), $size, $hue, e($name), e($initials ?: '?')
    );
}

function active(string $prefix): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $on = $prefix === '/' ? $path === '/' : str_starts_with((string) $path, $prefix);
    return $on ? 'active' : '';
}
