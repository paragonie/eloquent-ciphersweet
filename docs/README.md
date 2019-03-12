# Eloquent-CipherSweet Adapter Documentation

## Installation

This adapter can be installed through Composer:

```sh
composer require paragonie/eloquent-ciphersweet
```

We do not support non-Composer use-cases with this adapter library.

## Configuration

Once you've installed , publish `config/ciphersweet.php` with `php artisan vendor:publish` and then run the following
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

Override the `configureCipherSweet()` method to add columns to the bare
`EncryptedMultiRows` object.

```php
<?php
namespace YourCompany\YourApp;

use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\CipherSweet\Transformation\LastFourDigits;
use ParagonIE\EloquentCipherSweet\EncryptedFieldModel;

class Blah extends EncryptedFieldModel
{
    /**
     * @param EncryptedMultiRows $multiRows
     * @return EncryptedMultiRows
     */
    public function configureCipherSweet(
        EncryptedMultiRows $multiRows
    ): EncryptedMultiRows {
        return $multiRows
            ->addTable('sql_table_name')
                ->addTextField('sql_table_name', 'column1')
                ->addBooleanField('sql_table_name', 'column2')
                ->addFloatField('sql_table_name', 'column3')
                ->addBlindIndex(
                    'sql_table_name',
                    'column1',
                    new BlindIndex('sql_table_name_column1_index_1', [], 8)
                )
                ->addCompoundIndex(
                    'sql_table_name',
                     (new CompoundIndex(
                         'sql_table_name_compound',
                         ['column1', 'column2'],
                         4,
                         true
                     ))->addTransform('column1', new LastFourDigits())
                )
            ->addTable('other_table');
    }
}
```

If you're not familiar with the `EncryptedMultiRows` API, please refer to the
relevant section of the [CipherSweet documentation](https://github.com/paragonie/ciphersweet/tree/master/docs#encryptedmultirows).

## Creating a Custom Key Provider

If you would like to use your own custom key provider implementation, e.g. to integrate with AWS KMS, specify 'custom'
as your option for 'provider' in `config/ciphersweet.php`, uncomment the 'via' line and replace the class there with the
name of your own key provider factory class. It should implement the `__invoke` method and return a class that
implements `\ParagonIE\CipherSweet\Contract\KeyProviderInterface`. `__invoke` will be passed an instance of
`\ParagonIE\CipherSweet\Contract\BackendInterface` as its sole argument.

## Storing and Searching on Encrypted Data
