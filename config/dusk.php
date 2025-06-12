<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dusk Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the environment that Dusk will run in. By default,
    | this is set to "testing" but you may change it to any value you wish.
    |
    */

    'env' => env('DUSK_ENV', 'testing'),

    'driver' => 'chrome',
];