<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Model;

abstract class EncryptedFieldModel extends Model
{
    use CipherSweet;
}
