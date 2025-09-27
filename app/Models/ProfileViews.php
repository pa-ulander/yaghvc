<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @method static \Database\Factories\ProfileViewsFactory factory(...$parameters)
 *
 * @package App\Models
 */
class ProfileViews extends Model
{
    /** @use HasFactory<\Database\Factories\ProfileViewsFactory> */
    use HasFactory;

    public const UPDATED_AT = null;
    /**
     * The attributes that are mass assignable.
     */
    /** @var list<string> */
    protected $fillable = [
        'username',
        'repository',
        'visit_count',
        'last_visit',
    ];

    public $timestamps = true;

    /** @var array<string,string> */
    protected $casts = [
        'last_visit' => 'datetime',
        'visit_count' => 'int',
    ];

    public function getCount(string $username, ?string $repository = null): int
    {
        $cacheKey = $repository ? "count-{$username}-{$repository}" : "count-{$username}";

        /** @var int $count */
        $count = Cache::remember(key: $cacheKey, ttl: 1, callback: function () use ($username, $repository): int {
            $query = self::query()->where(column: 'username', operator: '=', value: $username);

            if ($repository !== null) {
                $query->where(column: 'repository', operator: '=', value: $repository);
            } else {
                $query->whereNull('repository');
            }

            $profileView = $query->first(['visit_count']);
            if ($profileView === null) {
                return 0;
            }

            return (int) ($profileView->visit_count ?? 0);
        });

        return $count;
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

    /**
     * @return \Database\Factories\ProfileViewsFactory
     */
    protected static function newFactory(): \Database\Factories\ProfileViewsFactory
    {
        return \Database\Factories\ProfileViewsFactory::new();
    }
}
