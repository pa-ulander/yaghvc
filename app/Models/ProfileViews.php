<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProfileViews extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'repository',
        'visit_count',
        'last_visit',
    ];

    public $timestamps = true;

    protected $casts = [
        'last_visit' => 'datetime',
    ];

    public function getCount(string $username, ?string $repository = null): mixed
    {
        $cacheKey = $repository ? "count-{$username}-{$repository}" : "count-{$username}";

        return Cache::remember(key: $cacheKey, ttl: 1, callback: function () use ($username, $repository): int {
            $query = self::where(column: 'username', operator: $username);

            if ($repository !== null) {
                $query->where(column: 'repository', operator: $repository);
            } else {
                $query->whereNull('repository');
            }

            $profileView = $query->first();
            return $profileView->visit_count ?? 0;
        });
    }

    public function incrementCount(): void
    {
        if (!$this->username) {
            Log::error(message: 'Attempt to increment count for ProfileView without username', context: ['id' => $this->id]);
            throw new \InvalidArgumentException(message: 'Username is missing for this instance.');
        }

        $this->increment(column: 'visit_count');
        $this->update(attributes: ['last_visit' => now()]);

        // Clear both profile and repository-specific caches
        $cacheKey = $this->repository ? "count-{$this->username}-{$this->repository}" : "count-{$this->username}";
        Cache::forget(key: $cacheKey);
    }
}
