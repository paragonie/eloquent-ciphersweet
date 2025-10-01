# Eloquent-CipherSweet Adapter

[![Build Status](https://github.com/paragonie/eloquent-ciphersweet/actions/workflows/ci.yml/badge.svg)](https://github.com/paragonie/eloquent-ciphersweet/actions)
[![Latest Stable Version](https://poser.pugx.org/paragonie/eloquent-ciphersweet/v/stable)](https://packagist.org/packages/paragonie/eloquent-ciphersweet)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/eloquent-ciphersweet/v/unstable)](https://packagist.org/packages/paragonie/eloquent-ciphersweet)
[![License](https://poser.pugx.org/paragonie/eloquent-ciphersweet/license)](https://packagist.org/packages/paragonie/eloquent-ciphersweet)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/eloquent-ciphersweet.svg)](https://packagist.org/packages/paragonie/eloquent-ciphersweet)

> [!IMPORTANT]
> This adapter is still being developed. It's only being open sourced so
> it may be tested in a Laravel application. Please don't use it yet.

---

This library allows for [searchable encryption](https://paragonie.com/blog/2017/05/building-searchable-encrypted-databases-with-php-and-sql)
in Eloquent ORM models.

## Installation

This adapter can be installed through Composer:

```sh
composer require paragonie/eloquent-ciphersweet
```

We do not support non-Composer use-cases with this adapter library.

## Configuration

Once you've installed, publish `config/ciphersweet.php` with `php artisan vendor:publish` and then run the following
artisan command to set your key.

```
php artisan ciphersweet:generate:key
```

Once the configuration is done, you can begin using encrypted fields in your models.

There are two ways to achieve this effect:

### EncryptedFieldModel Base Class

The easiest way to use the features of the adapter is to ensure your models extend
`EncryptedFieldModel` instead of the base `Model`.

```diff
<?php
- use Illuminate\Database\Eloquent\Model;
+ use ParagonIE\EloquentCipherSweet\EncryptedFieldModel;

- class Foo extends Model
+ class Foo extends EncryptedFieldModel
```

This automatically loads in the trait and boots it for you. If you use this in a base
class, and some of your classes that inherit that base class *don't* need encrypted fields,
you can simply leave them un-configured.

### CipherSweet Trait

If this is not tenable due to existing object inheritance requirements, you may also
simply use the `CipherSweet` trait, like so.

```php
<?php
use Illuminate\Database\Eloquent\Model;
use ParagonIE\EloquentCipherSweet\CipherSweet;

class Blah extends Model
{
    use CipherSweet;
}
```

## Defining Encrypted Fields

Override the `configureCipherSweet()` method to define your encrypted fields and blind indexes.

Every field that will be used in database lookups should have a blind index attached to it.

```php
<?php
namespace YourCompany\YourApp;

use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\EncryptedFieldModel;

class Example extends EncryptedFieldModel
{
    /**
     * @param EncryptedMultiRows $multiRows
     * @return void
     */
    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('users')
            ->addTextField('users', 'name')
            ->addBlindIndex(
                'users',
                'name',
                new BlindIndex('users_name_bi')
            )
            ->addTextField('users', 'email')
            ->addBlindIndex(
                'users',
                'email',
                new BlindIndex('users_email_bi', [], 16)
            );
    }
}
```

If you're not familiar with the `EncryptedMultiRows` API, please refer to the
relevant section of the [CipherSweet documentation](https://ciphersweet.paragonie.com/php/usage#encryptedmultirows).

## Creating a Custom Key Provider

If you would like to use your own custom key provider implementation, e.g. to integrate with AWS KMS, specify 'custom'
as your option for 'provider' in `config/ciphersweet.php`, uncomment the 'via' line and replace the class there with the
name of your own key provider factory class. It should implement the `__invoke` method and return a class that
implements `\ParagonIE\CipherSweet\Contract\KeyProviderInterface`. `__invoke` will be passed an instance of
`\ParagonIE\CipherSweet\Contract\BackendInterface` as its sole argument.

## Storing and Searching on Encrypted Data

Once you have configured your model, the encryption and decryption of data is handled automatically by the model
observer. When you save a model, the encrypted fields will be encrypted, and when you retrieve a model, they will be
decrypted.

To search on encrypted data, you can use the `whereBlind` scope. This scope will automatically calculate the blind index
for the given value and use it in the query.

```php
// Find a user by their email address:
$user = Example::whereBlind('email', 'test@example.com')->first();

// You can also specify the blind index name if it doesn't follow the convention:
$user = Example::whereBlind('email', 'test@example.com', 'my_custom_email_index')->first();
```

## Support Contracts

If your company uses this library in their products or services, you may be
interested in [purchasing a support contract from Paragon Initiative Enterprises](https://paragonie.com/enterprise).
