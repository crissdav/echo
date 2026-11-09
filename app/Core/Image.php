<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Procesamiento de imágenes con GD:
 *  - valida el tipo real del archivo (no la extensión),
 *  - corrige la orientación EXIF de fotos de celular,
 *  - recorta a una proporción entre 4:5 y 1.91:1 (como Instagram),
 *  - genera versión grande (1080 px) y miniatura cuadrada (400 px),
 *  - re-codifica en JPEG, lo que elimina metadatos (GPS, etc.).
 */
final class Image
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return array{full:string, thumb:string, width:int, height:int}
     */
    public static function processUpload(array $file, bool $square = false): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(match ($file['error'] ?? 0) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo (8 MB).',
                UPLOAD_ERR_NO_FILE => 'Selecciona una imagen.',
                default => 'No se pudo subir la imagen.',
            });
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('La imagen supera el tamaño máximo (8 MB).');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new \InvalidArgumentException('Formato no permitido. Usa JPG, PNG o WEBP.');
        }
        $src = match ($mime) {
            'image/png'  => imagecreatefrompng($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
            default      => imagecreatefromjpeg($file['tmp_name']),
        };
        if (!$src) {
            throw new \InvalidArgumentException('La imagen está dañada.');
        }
        if ($mime === 'image/jpeg') {
            $src = self::fixOrientation($src, $file['tmp_name']);
        }
        return self::fromGd($src, $square);
    }

    /** Procesa una imagen GD ya cargada (lo usa también el seeder). */
    public static function fromGd(\GdImage $src, bool $square = false): array
    {
        $w = imagesx($src);
        $h = imagesy($src);

        // Recorte central a proporción permitida.
        $ratio = $w / $h;
        $min = $square ? 1.0 : 0.8;   // 4:5 vertical
        $max = $square ? 1.0 : 1.91;  // horizontal
        $cw = $w; $ch = $h;
        if ($ratio < $min) { $ch = (int) round($w / $min); }
        if ($ratio > $max) { $cw = (int) round($h * $max); }
        $cx = intdiv($w - $cw, 2);
        $cy = intdiv($h - $ch, 2);

        $fullW = min(1080, $cw);
        $fullH = (int) round($ch * $fullW / $cw);
        $full = self::resample($src, $cx, $cy, $cw, $ch, $fullW, $fullH);

        // Miniatura cuadrada para cuadrículas de perfil/explorar.
        $side = min($w, $h);
        $thumb = self::resample($src, intdiv($w - $side, 2), intdiv($h - $side, 2), $side, $side, 400, 400);

        $fullPath  = tempnam(sys_get_temp_dir(), 'echo_full_');
        $thumbPath = tempnam(sys_get_temp_dir(), 'echo_th_');
        imagejpeg($full, $fullPath, 84);
        imagejpeg($thumb, $thumbPath, 80);

        return ['full' => $fullPath, 'thumb' => $thumbPath, 'width' => $fullW, 'height' => $fullH];
    }

    private static function resample(\GdImage $src, int $sx, int $sy, int $sw, int $sh, int $dw, int $dh): \GdImage
    {
        $dst = imagecreatetruecolor($dw, $dh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // fondo para PNG transparentes
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
        return $dst;
    }

    private static function fixOrientation(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        return match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }

    /** Genera una clave única en S3, ej. posts/2026/11/9f2c...e1.jpg */
    public static function key(string $folder, string $suffix = ''): string
    {
        return sprintf('%s/%s/%s%s.jpg', $folder, date('Y/m'), bin2hex(random_bytes(12)), $suffix);
    }
}
