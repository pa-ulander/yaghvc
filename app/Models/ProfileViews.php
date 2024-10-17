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

    public function getCount($username)
    {
        return Cache::remember(key: 'count-' . $username, ttl: 1, callback: function () use ($username): int {
            $profileView = self::where(column: 'username', operator: $username)->first();
            return $profileView->username ? $profileView->visit_count : 0;
        });
    }

    public function incrementCount()
    {
        if (!$this->username) {
            Log::error('Attempt to increment count for ProfileView without username', ['id' => $this->id]);
            throw new \InvalidArgumentException('Username is missing for this instance.');
        }
    
        $this->increment('visit_count');
        $this->update(['last_visit' => now()]);
    
        Cache::forget("count-{$this->username}");
    }
}
