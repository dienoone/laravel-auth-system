<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class ProfileService
{
  protected FileUploadService $fileUploadService;

  public function __construct(FileUploadService $fileUploadService)
  {
    $this->fileUploadService = $fileUploadService;
  }

  /**
   * Update user profile
   */
  public function updateProfile(User $user, array $data): User
  {
    return DB::transaction(function () use ($user, $data) {
      // Handle avatar upload
      if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
        $data['avatar'] = $this->fileUploadService->uploadAvatar(
          $data['avatar'],
          $user->avatar
        );
      }

      // Handle avatar removal
      if (isset($data['remove_avatar']) && $data['remove_avatar']) {
        if ($user->avatar) {
          $this->fileUploadService->deleteAvatar($user->avatar);
        }
        $data['avatar'] = null;
        unset($data['remove_avatar']);
      }

      // Update user data
      $user->update(array_filter($data, function ($value) {
        return !is_null($value);
      }));

      return $user->fresh();
    });
  }

  /**
   * Get user profile with statistics
   */
  public function getProfileWithStats(User $user): array
  {
    return [
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'avatar' => $user->avatar ?? $this->fileUploadService->getDefaultAvatar(),
        'phone' => $user->phone,
        'email_verified' => $user->email_verified,
        'email_verified_at' => $user->email_verified_at,
        'two_factor_enabled' => $user->two_factor_enabled,
        'is_active' => $user->is_active,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
      ],
      'roles' => $user->roles->map(function ($role) {
        return [
          'id' => $role->id,
          'name' => $role->name,
          'slug' => $role->slug
        ];
      }),
      'permissions' => $user->getAllPermissions()->map(function ($permission) {
        return [
          'id' => $permission->id,
          'name' => $permission->name,
          'slug' => $permission->slug
        ];
      }),
      'stats' => [
        'last_login_at' => $user->last_login_at,
        'last_login_ip' => $user->last_login_ip,
        'account_age_days' => $user->created_at->diffInDays(now()),
        'is_locked' => $user->isLocked(),
        'locked_until' => $user->locked_until,
      ]
    ];
  }
}
