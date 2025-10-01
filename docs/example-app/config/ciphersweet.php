<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cryptographic Backend
    |--------------------------------------------------------------------------
    |
    | This controls which cryptographic backend will be used by CipherSweet.
    | Unless you have specific compliance requirements, you should choose
    | "nacl".
    |
    | Supported: "fips", "nacl"
    |
    */

    'backend' => env('CIPHERSWEET_BACKEND', 'brng'),

    /*
    |--------------------------------------------------------------------------
    | Key Provider
    |--------------------------------------------------------------------------
    |
    | Select which key provider your application will use. The default option
    | is to read a string literal out of .env, but it's also possible to
    | provide the key in a file, use a custom key provider, or use random keys
    | for testing.
    |
    | "string" is selected by default to read a key directly from your .env
    | file. Use `artisan ciphersweet:generate:key` to securely generate that
    | key.
    |
    | Supported: "custom", "file", "random", "string"
    |
    */

    'provider' => env('CIPHERSWEET_PROVIDER', 'string'),

    /*
    |--------------------------------------------------------------------------
    | Key Providers
    |--------------------------------------------------------------------------
    |
    | Set provider-specific options here. "string" will read the key directly
    | from your .env file. "file" will read the contents of the specified file
    | to use as your key. "custom" points to a factory class that returns a
    | provider from its `__invoke` method. Please see the docs for more details.
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
            'key' => env('APP_ENV') === 'testing'
                ? 'c00051417523c9e9584e35ee5bfa6ce62935671546467cf670bc3b130e0b5e25'
                : env('CIPHERSWEET_KEY'),
        ],
    ],
];
