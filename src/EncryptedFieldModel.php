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

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
    }
}
