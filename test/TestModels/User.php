<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\CipherSweet;

class User extends Model
{
    use CipherSweet;

    protected $table = 'users';

    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('users')
            ->addTextField('users', 'name')
            ->addBlindIndex(
                'users',
                'name',
                new BlindIndex('name_bi')
            )
            ->addTextField('users', 'email')
            ->addBlindIndex(
                'users',
                'email',
                new BlindIndex('email_bi', [], 16)
            );
    }
}
