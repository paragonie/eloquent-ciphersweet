# Eloquent-CipherSweet Usage

This document will guide you through setting up and using the Eloquent-CipherSweet adapter in your Laravel projects.

## Installation

First, install the library via Composer:

```bash
composer require paragonie/eloquent-ciphersweet
```

## Configuration

After installation, you need to publish the configuration file:

```bash
php artisan vendor:publish --provider="ParagonIE\EloquentCipherSweet\ServiceProvider"
```

This will create a `config/ciphersweet.php` file. Next, generate an encryption key:

```bash
php artisan ciphersweet:generate:key
```

This command will add the `CIPHERSWEET_KEY` to your `.env` file. By default, the `config/ciphersweet.php` file is
configured to use this environment variable.

## Usage

There are two ways to add CipherSweet functionality to your models.

### Using the `EncryptedFieldModel`

The simplest method is to extend `ParagonIE\EloquentCipherSweet\EncryptedFieldModel`:

```php
<?php

namespace App\Models;

use ParagonIE\EloquentCipherSweet\EncryptedFieldModel;

class User extends EncryptedFieldModel
{
    // ... your model properties and methods
}
```

### Using the `CipherSweet` Trait

If you cannot change your model's base class, you can use the `ParagonIE\EloquentCipherSweet\CipherSweet` trait instead:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use ParagonIE\EloquentCipherSweet\CipherSweet;

class User extends Authenticatable
{
    use CipherSweet;

    // ... your model properties and methods
}
```

### Defining Encrypted Fields

To specify which fields should be encrypted, you need to implement the `configureCipherSweet()` static method in your
model. This is where you define your encrypted columns and any blind indexes for searching.

Here is an example from the [example app](example-app) of how to configure the `User` model to encrypt the `name` and
`email` fields:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\CipherSweet;

class User extends Authenticatable
{
    use HasFactory, Notifiable, CipherSweet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @param EncryptedMultiRows $multiRows
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\CipherSweetException
     * @throws \SodiumException
     */
    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('users')
            ->addTextField('name')
            ->addBlindIndex('name', new BlindIndex('users_name_bi'))
            ->addTextField('email')
            ->addBlindIndex('email', new BlindIndex('users_email_bi'));
    }
}
```

For more details on configuring `EncryptedMultiRows`, please see the 
[CipherSweet documentation](https://ciphersweet.paragonie.com/php/usage#encryptedmultirows).

### Storing and Searching Encrypted Data

Once your model is configured, encryption and decryption are handled automatically. When you save a model, the
configured fields will be encrypted. When you retrieve a model, they will be decrypted.

To search on an encrypted field, you must use the `whereBlind()` query scope, which uses the blind index you configured.

```php
// Find a user by their email address:
$user = User::whereBlind('email', 'test@example.com')->first();

// You can also specify the blind index name if it doesn't follow the convention:
$user = User::whereBlind('email', 'test@example.com', 'users_email_bi')->first();
```

## Example App

The [example-app](example-app) directory contains an example Symfony application that uses the Eloquent-CipherSweet 
adapter. This example app is tested as part of our CI/CD pipeline, so the code there is guaranteed to work if the build
passes.
