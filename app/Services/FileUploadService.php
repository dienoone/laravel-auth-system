<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
  /**
   * Upload avatar
   */
  public function uploadAvatar(UploadedFile $file, ?string $oldAvatar = null): string
  {
    // Generate unique filename
    $filename = 'avatar_' . Str::random(20) . '_' . time() . '.' . $file->getClientOriginalExtension();

    // Store file
    $path = $file->storeAs('avatars', $filename, 'public');

    // Delete old avatar if exists
    if ($oldAvatar) {
      $this->deleteAvatar($oldAvatar);
    }

    return Storage::url($path);
  }

  /**
   * Delete avatar
   */
  public function deleteAvatar(string $avatarPath): bool
  {
    // Extract the path from the full URL
    $path = str_replace('/storage/', '', parse_url($avatarPath, PHP_URL_PATH));

    if ($path && Storage::disk('public')->exists($path)) {
      return Storage::disk('public')->delete($path);
    }

    return false;
  }

  /**
   * Get default avatar URL
   */
  public function getDefaultAvatar(): string
  {
    return asset('images/default-avatar.png');
  }
}
