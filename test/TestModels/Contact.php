<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\CipherSweet;

class Contact extends Model
{
    use CipherSweet;

    protected $table = 'contacts';

    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('contacts')
            ->addTextField('contacts', 'name')
            ->addBlindIndex(
                'contacts',
                'name',
                new BlindIndex('name_bi')
            )
            ->addTextField('contacts', 'email')
            ->addBlindIndex(
                'contacts',
                'email',
                new BlindIndex('my_custom_email_index')
            );
    }
}