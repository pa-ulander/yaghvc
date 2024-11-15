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

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'visit_count',
        'last_visit',
    ];

    public $timestamps = true;

    protected $casts = [
        'last_visit' => 'datetime',
    ];

    public function getCount(string $username): mixed
    {
        return Cache::remember(key: 'count-' . $username, ttl: 1, callback: function () use ($username): int {
            $profileView = self::where(column: 'username', operator: $username)->first();
            return $profileView->username ? $profileView->visit_count : 0;
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
    
        Cache::forget(key: "count-{$this->username}");
    }
}
