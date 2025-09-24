<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description'
    ];

    /**
     * Get permissions grouped by category
     */
    public static function getGroupedByCategory()
    {
        return self::all()->groupBy('category');
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return self::distinct('category')->pluck('category')->toArray();
    }

    /**
     * Scope to filter by category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the permission's roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Get the permission's users
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withTimestamps();
    }
}
