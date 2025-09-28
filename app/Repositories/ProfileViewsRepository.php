<?php

namespace App\Repositories;

use App\Models\ProfileViews;

/** @package App\Repositories */
class ProfileViewsRepository
{
    public function findOrCreate(string $username, ?string $repository = null): ProfileViews
    {
        $attributes = ['username' => $username];
        $values = ['visit_count' => 0, 'last_visit' => now()];

        if ($repository !== null) {
            $attributes['repository'] = $repository;
        }

        $profileView = ProfileViews::firstOrCreate(
            attributes: $attributes,
            values: $values
        );

        $profileView->incrementCount();

        return $profileView;
    }
}
