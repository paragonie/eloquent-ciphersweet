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

    'backend' => env('CIPHERSWEET_BACKEND', 'nacl'),

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
            'key' => env('CIPHERSWEET_KEY', '075888e79c3166f38fc12a8b658b3991ffae4e78ac4740332a8ea9e955e4ec77'),
        ],
    ],

    /*
     * This is the name of the table that will be created by the migration and
     * used by the CipherSweet model shipped with this package.
     */
    'table_name' => 'blind_indexes',

    /*
     * This is the database connection that will be used by the migration and
     * the CipherSweet model shipped with this package.
     */
    'database_connection' => env('CIPHERSWEET_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
];