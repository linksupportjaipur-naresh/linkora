<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Saves uploaded images to the public disk as optimised JPEGs.
 */
class ImageStore
{
    /**
     * Background photo: scaled down to fit inside $maxW × $maxH (never enlarged),
     * re-encoded as JPEG so phones load it quickly. Returns the stored path.
     */
    public static function background(UploadedFile $file, int $userId, int $maxW = 1200, int $maxH = 2000): string
    {
        $path = 'backgrounds/' . $userId . '-' . Str::random(12) . '.jpg';
        $src = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (! $src) {
            return $file->store('backgrounds', 'public');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $maxW / $w, $maxH / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten transparent PNGs onto white
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, 82);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }
}
