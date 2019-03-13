<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EncryptedFieldModel
 * @package ParagonIE\EloquentCipherSweet
 */
class EncryptedFieldModel extends Model
{
    use CipherSweet;
}
