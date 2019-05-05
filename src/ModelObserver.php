<?php

namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ModelObserver
{
    /**
     * @param Model|CipherSweet $model
     * @throws \SodiumException
     */
    public function deleting(Model $model)
    {
        if ($types = $model::getBlindIndexTypes()) {
            DB::table('blind_indexes')
                ->whereIn('type', $types)
                ->where('foreign_id', $model->getKey())
                ->delete();
        }
    }

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
