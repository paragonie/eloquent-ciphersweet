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

    'provider' => env('CIPHERSWEET_PROVIDER', 'random'),

    /*
    |--------------------------------------------------------------------------
    | Key Providers
    |--------------------------------------------------------------------------
    |
    | Configure the
    | Supported: "file", "random", "string"
    |
    */

    'providers' => [
        'file' => [
            'path' => env('CIPHERSWEET_FILE_PATH'),
        ],
        'string' => [
            'key' => env('CIPHERSWEET_KEY'),
        ],
    ],
];