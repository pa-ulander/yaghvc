<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileViews;
use App\Http\Requests\ProfileViewRequest;

class ProfileViewsController extends Controller
{

    public function index(ProfileViewRequest $request)
    {
        $profileView = new ProfileViews;

        $profileView->username = $request->username;
        $profileView->save();

        // should return code to generate the badge
        return [
            'username' => $request->username,
            'views' => $profileView->count($request->username)
        ];
    }
}
