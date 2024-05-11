<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileViews;

class ProfileViewsController extends Controller
{

    public function index(Request $request)
    {
        if (!$request->username) return false;

        // Validate the request...

        $profileView = new ProfileViews;
        $profileView->username = $request->username;
        $profileView->save();

        // should return code to generate the badge
        return [
            'status' => 200,
            'username' => $request->username
        ];
    }
}
