<?php

return [
    'host' => 'cloakr.dev',
    'port' => 8080,
    'auth_token' => '',

    'admin' => [

        /*
        |--------------------------------------------------------------------------
        | Subdomain
        |--------------------------------------------------------------------------
        |
        | This is the subdomain that your cloakr admin dashboard will be available at.
        | The given subdomain will be reserved, so no other tunnel connection can
        | request this subdomain for their own connection.
        |
        */
        'subdomain' => 'cloakr',

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        |
        | The admin dashboard of cloakr is protected via HTTP basic authentication
        | Here you may add the user/password combinations that you want to
        | accept as valid logins for the dashboard.
        |
        */
        'users' => [
            'username' => 'password'
        ]
    ]
];
