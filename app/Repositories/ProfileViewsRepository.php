<?php

namespace App\Repositories;

use App\Models\ProfileViews;

class ProfileViewsRepository
{
    public function findOrCreate(string $username): ProfileViews
    {
        $profileView = ProfileViews::firstOrCreate(
            attributes: ['username' => $username],
            values: ['visit_count' => 0, 'last_visit' => now()]
        );
        
        $profileView->incrementCount();

        return $profileView;
    }
}
