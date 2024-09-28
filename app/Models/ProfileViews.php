<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        return Cache::remember('count-' . $username, 1, function () use ($username): int {
            $profileView = self::where('username', $username)->first();
            return $profileView->username ? $profileView->visit_count : 0;
        });
    }

    public function incrementCount()
    {
        if ($this->username) {
            $this->visit_count++;
            $this->last_visit = now();
            $this->save();
        } else {
            throw new \Exception("Username is missing for this instance.");
        }
    }
}
