<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * College logos live in public/image/colleges/<college code>.png (for example ccis.png).
 * Uploaded logos are converted to a uniform 600x600 PNG with a white background,
 * so every college box on the Browse page looks the same size.
 */
class CollegeLogo
{
    private const DIR = 'image/colleges';
    private const SIZE = 600;     // final square size in pixels
    private const BOX = 560;      // largest side of the logo inside the square (20px margin)
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    /** Relative path of the logo for a college code, or null if there is none. */
    public static function path(string $code): ?string
    {
        foreach (self::EXTENSIONS as $ext) {
            $file = self::DIR.'/'.strtolower($code).'.'.$ext;
            if (is_file(public_path($file))) {
                return $file;
            }
        }

        return null;
    }

    /** Public URL (with a version number so browsers load a replaced logo right away). */
    public static function url(string $code): ?string
    {
        $path = self::path($code);

        return $path ? asset($path).'?v='.filemtime(public_path($path)) : null;
    }

    public static function save(string $code, UploadedFile $file): void
    {
        self::delete($code);   // remove any old logo (any extension)

        $dir = public_path(self::DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $target = $dir.'/'.strtolower($code).'.png';

        $source = self::open($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        if (! $source) {
            // Image library not available: keep the original file as it is
            $file->move($dir, strtolower($code).'.'.strtolower($file->getClientOriginalExtension()));

            return;
        }

        // Fit the logo inside a white square, centred
        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(self::BOX / $w, self::BOX / $h);
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, (int) ((self::SIZE - $nw) / 2), (int) ((self::SIZE - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
        imagepng($canvas, $target, 6);
        imagedestroy($canvas);
        imagedestroy($source);
    }

    public static function delete(string $code): void
    {
        foreach (self::EXTENSIONS as $ext) {
            $file = public_path(self::DIR.'/'.strtolower($code).'.'.$ext);
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function open(string $path, string $ext)
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        return match ($ext) {
            'png' => @imagecreatefrompng($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        } ?: null;
    }
}
