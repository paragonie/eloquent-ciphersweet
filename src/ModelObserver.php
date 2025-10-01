<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Model;

final class ModelObserver
{
    /**
     * @param Model|CipherSweet $model
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    public function retrieved(Model $model)
    {
        $model->decryptRow();
    }

    /**
     * @param Model|CipherSweet $model
     * @throws \ParagonIE\CipherSweet\Exception\ArrayKeyException
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    public function saving(Model $model)
    {
        $model->encryptRow();
    }
}
