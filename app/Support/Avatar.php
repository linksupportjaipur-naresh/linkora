<?php

namespace App\Support;

use App\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Profile photo rules + storage, shared by My Profile and Settings so both behave the same.
 * The photo lives on the profile (profiles.avatar_path) and is shown on the public page and in the dashboard.
 */
class Avatar
{
    public static function rules(): array
    {
        return ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000'];
    }

    public static function messages(string $field = 'avatar'): array
    {
        return [
            $field . '.required'   => 'Please choose a photo.',
            $field . '.image'      => 'Please choose a JPG, PNG or WebP image.',
            $field . '.mimes'      => 'Please choose a JPG, PNG or WebP image.',
            $field . '.dimensions' => 'Please use a photo between 100 × 100 and 6000 × 6000 pixels.',
            $field . '.max'        => 'The photo must be smaller than 2 MB.',
            $field . '.uploaded'   => 'The photo could not be uploaded. Please try a smaller image.',
        ];
    }

    /** Save the new photo and delete the old uploaded one. */
    public static function replace(Profile $profile, UploadedFile $file): void
    {
        $old = $profile->avatar_path;
        $profile->avatar_path = self::store($file, $profile->user_id);
        $profile->save();
        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    /** Remove the uploaded photo (a Google photo, if any, shows again). */
    public static function remove(Profile $profile): void
    {
        if ($profile->avatar_path) {
            Storage::disk('public')->delete($profile->avatar_path);
            $profile->avatar_path = null;
            $profile->save();
        }
    }

    /** Save a square 400×400 JPEG copy so every avatar is small and consistent. */
    public static function store(UploadedFile $file, int $userId): string
    {
        $path = 'avatars/' . $userId . '-' . Str::random(12) . '.jpg';
        $src = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (! $src) {
            return $file->store('avatars', 'public');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $dst = imagecreatetruecolor(400, 400);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), 400, 400, $side, $side);

        ob_start();
        imagejpeg($dst, null, 86);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }
}
