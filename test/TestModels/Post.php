<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Tests\TestModels;

use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\EloquentCipherSweet\EncryptedFieldModel;

class Post extends EncryptedFieldModel
{
    protected $table = 'posts';

    protected static function configureCipherSweet(EncryptedMultiRows $multiRows): void
    {
        $multiRows
            ->addTable('posts')
            ->addTextField('posts', 'title')
            ->addBlindIndex(
                'posts',
                'title',
                new BlindIndex('title_bi')
            )
            ->addTextField('posts', 'body')
            ->addBlindIndex(
                'posts',
                'body',
                new BlindIndex('body_bi', [], 16)
            );
    }
}