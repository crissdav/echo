<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Almacenamiento de imágenes.
 *  - Driver "s3": bucket PRIVADO en Amazon S3. La EC2 se autentica con su rol IAM
 *    (sin access keys en el código) y el navegador recibe URLs prefirmadas temporales.
 *  - Driver "local": carpeta storage/uploads, solo para desarrollo.
 */
final class Storage
{
    private static ?object $s3 = null;
    private static array $urlCache = [];

    public static function driver(): string
    {
        return env('STORAGE_DRIVER', 'local') === 's3' ? 's3' : 'local';
    }

    private static function s3(): object
    {
        if (self::$s3 === null) {
            if (!class_exists(\Aws\S3\S3Client::class)) {
                throw new \RuntimeException('Falta el AWS SDK. Ejecuta: composer install');
            }
            // Sin 'credentials': el SDK usa automáticamente el rol IAM de la instancia EC2.
            self::$s3 = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => env('AWS_REGION', 'us-east-2'),
            ]);
        }
        return self::$s3;
    }

    /** Sube un archivo local con la clave indicada (ej. posts/2026/11/abc.jpg). */
    public static function put(string $key, string $localFile, string $mime = 'image/jpeg'): void
    {
        if (self::driver() === 's3') {
            self::s3()->putObject([
                'Bucket'       => env('S3_BUCKET'),
                'Key'          => $key,
                'SourceFile'   => $localFile,
                'ContentType'  => $mime,
                'CacheControl' => 'private, max-age=86400',
            ]);
            return;
        }
        $dest = BASE_PATH . '/storage/uploads/' . $key;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0775, true);
        }
        copy($localFile, $dest);
    }

    /** URL para mostrar la imagen en el navegador. */
    public static function url(?string $key): string
    {
        if (!$key) {
            return '';
        }
        if (isset(self::$urlCache[$key])) {
            return self::$urlCache[$key];
        }
        if (self::driver() === 's3') {
            $ttl = (int) env('S3_URL_TTL', '7200');
            // Firmamos con una hora "redondeada" para que la URL sea estable durante
            // un rato y el navegador pueda reutilizar la imagen de su caché.
            $window = max(600, intdiv($ttl, 2));
            $start  = intdiv(time(), $window) * $window;
            $cmd = self::s3()->getCommand('GetObject', ['Bucket' => env('S3_BUCKET'), 'Key' => $key]);
            $req = self::s3()->createPresignedRequest($cmd, $start + $ttl, ['start_time' => $start]);
            return self::$urlCache[$key] = (string) $req->getUri();
        }
        return self::$urlCache[$key] = '/media/' . $key;
    }

    public static function delete(?string $key): void
    {
        if (!$key) {
            return;
        }
        if (self::driver() === 's3') {
            self::s3()->deleteObject(['Bucket' => env('S3_BUCKET'), 'Key' => $key]);
            return;
        }
        $file = BASE_PATH . '/storage/uploads/' . $key;
        if (is_file($file)) {
            unlink($file);
        }
    }

    /** Comprueba el acceso al bucket (para /health). */
    public static function ping(): bool
    {
        if (self::driver() === 's3') {
            self::s3()->headBucket(['Bucket' => env('S3_BUCKET')]);
            return true;
        }
        return is_writable(BASE_PATH . '/storage/uploads');
    }
}
