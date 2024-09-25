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
    ];

    public function count($username)
    {
        return Cache::remember('count-' . $username, 1, function () use ($username) {
            $tableName = 'profile_views';
            return DB::table($tableName)
                ->where('username', '=', $username)
                ->count();
        });
    }
}
