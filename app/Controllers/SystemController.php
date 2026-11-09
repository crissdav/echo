<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\Storage;
use App\Core\View;

/**
 * Estado de la infraestructura: muestra en qué instancia EC2 corre Echo,
 * a qué base RDS se conecta (y si la conexión va cifrada) y el estado de S3.
 */
final class SystemController
{
    /** JSON para monitoreo / pruebas con curl. */
    public function health(): void
    {
        $info = $this->collect();
        $ok = $info['db']['ok'] && $info['storage']['ok'];
        unset($info['stats']);
        json_response(['status' => $ok ? 'ok' : 'degraded'] + $info, $ok ? 200 : 503);
    }

    /** Página visual "Estado del sistema". */
    public function status(): void
    {
        Auth::require();
        View::render('system/status', ['title' => 'Estado del sistema', 'info' => $this->collect()]);
    }

    /** Sirve imágenes solo en modo local (con S3 nunca se usa). */
    public function media(string $key): void
    {
        if (Storage::driver() !== 'local') {
            http_response_code(404);
            return;
        }
        $base = realpath(BASE_PATH . '/storage/uploads');
        $file = realpath($base . '/' . $key);
        if (!$file || !str_starts_with($file, $base . DIRECTORY_SEPARATOR) || !is_file($file)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: image/jpeg');
        header('Cache-Control: private, max-age=86400');
        readfile($file);
    }

    private function collect(): array
    {
        // --- Base de datos (RDS) ---
        $db = ['ok' => false];
        try {
            $t = microtime(true);
            DB::value('SELECT 1');
            $db['latency_ms'] = round((microtime(true) - $t) * 1000, 2);
            $db['ok'] = true;
            $db['version'] = DB::value('SELECT VERSION()');
            $ssl = DB::one("SHOW SESSION STATUS LIKE 'Ssl_cipher'");
            $db['tls'] = !empty($ssl['Value']) ? $ssl['Value'] : null;
            $db['host'] = $this->maskHost((string) env('DB_HOST'));
            $db['name'] = env('DB_NAME');
            $db['user'] = env('DB_USER');
        } catch (\Throwable $e) {
            $db['error'] = 'Sin conexión';
        }

        // --- Almacenamiento (S3) ---
        $storage = ['driver' => Storage::driver(), 'ok' => false];
        try {
            $t = microtime(true);
            $storage['ok'] = Storage::ping();
            $storage['latency_ms'] = round((microtime(true) - $t) * 1000, 2);
            $storage['bucket'] = Storage::driver() === 's3' ? env('S3_BUCKET') : 'storage/uploads (local)';
            $storage['region'] = Storage::driver() === 's3' ? env('AWS_REGION') : null;
        } catch (\Throwable $e) {
            $storage['error'] = 'Sin acceso al bucket';
        }

        $stats = [];
        if ($db['ok']) {
            $stats = DB::one('SELECT * FROM v_estadisticas') ?? [];
        }

        return [
            'app'     => ['name' => env('APP_NAME', 'Echo'), 'env' => env('APP_ENV'), 'php' => PHP_VERSION,
                          'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI', 'time' => date('Y-m-d H:i:s')],
            'ec2'     => $this->instanceMetadata(),
            'db'      => $db,
            'storage' => $storage,
            'stats'   => $stats,
        ];
    }

    /** Metadatos de la instancia EC2 vía IMDSv2 (en caché 10 min). */
    private function instanceMetadata(): array
    {
        $cache = sys_get_temp_dir() . '/echo_ec2_meta.json';
        if (is_file($cache) && filemtime($cache) > time() - 600) {
            return json_decode((string) file_get_contents($cache), true) ?: ['available' => false];
        }
        $meta = ['available' => false];
        $token = $this->http('PUT', 'http://169.254.169.254/latest/api/token', ['X-aws-ec2-metadata-token-ttl-seconds: 300']);
        if ($token) {
            $h = ['X-aws-ec2-metadata-token: ' . $token];
            $meta = [
                'available'         => true,
                'instance_id'       => $this->http('GET', 'http://169.254.169.254/latest/meta-data/instance-id', $h),
                'instance_type'     => $this->http('GET', 'http://169.254.169.254/latest/meta-data/instance-type', $h),
                'availability_zone' => $this->http('GET', 'http://169.254.169.254/latest/meta-data/placement/availability-zone', $h),
                'private_ip'        => $this->http('GET', 'http://169.254.169.254/latest/meta-data/local-ipv4', $h),
                'iam_role'          => $this->http('GET', 'http://169.254.169.254/latest/meta-data/iam/security-credentials/', $h),
            ];
        }
        @file_put_contents($cache, json_encode($meta));
        return $meta;
    }

    private function http(string $method, string $url, array $headers): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 400,
            CURLOPT_TIMEOUT_MS     => 800,
            CURLOPT_PROXY          => '',
        ]);
        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($out !== false && $code === 200) ? trim((string) $out) : null;
    }

    /** echo-db.abc123xyz.us-east-2.rds.amazonaws.com → echo-db.abc•••.us-east-2.rds.amazonaws.com */
    private function maskHost(string $host): string
    {
        $parts = explode('.', $host);
        if (count($parts) > 3 && !filter_var($host, FILTER_VALIDATE_IP)) {
            $parts[1] = substr($parts[1], 0, 3) . '•••';
        }
        return implode('.', $parts);
    }
}
