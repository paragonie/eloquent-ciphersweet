<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Backend
    |--------------------------------------------------------------------------
    |
    | This controls which cryptographic backend will be used by CipherSweet.
    | Unless you have specific compliance requirements, you should choose
    | "nacl".
    |
    | Supported: "fips", "nacl"
    |
    */

    'backend' => env('CIPHERSWEET_BACKEND', 'nacl'),

    /*
    |--------------------------------------------------------------------------
    | Default Key Provider
    |--------------------------------------------------------------------------
    */

    'provider' => env('CIPHERSWEET_PROVIDER', 'string'),

    /*
    |--------------------------------------------------------------------------
    | Key Providers
    |--------------------------------------------------------------------------
    |
    | Configure the
    | Supported: "custom", "file", "random", "string"
    |
    */

    'providers' => [
        'custom' => [
            //'via' => \App\CipherSweetKey\CreateKeyProvider::class,
        ],
        'file' => [
            'path' => env('CIPHERSWEET_FILE_PATH'),
        ],
        'string' => [
            'key' => env('CIPHERSWEET_KEY'),
        ],
    ],
];