<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Arr;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\Contract\TransformationInterface;
use ParagonIE\CipherSweet\EncryptedRow;

/**
 * Trait CipherSweet
 *
 * Makes integrating CipherSweet with Eloquent ORM much easier.
 *
 * @method EloquentBuilder whereBlind(string $column, string $indexName, string $value)
 * @package ParagonIE\EloquentCipherSweet
 */
trait CipherSweet
{
    /** @var EncryptedRow */
    protected static $cipherSweetEncryptedRow;

    protected static $cipherSweetIndexes = [];

    protected static $cipherSweetFields = [];

    /** @var array<string,string|array<string>> */
    private static $indexToField = [];

    /**
     * @return void
     */
    protected static function bootCipherSweet()
    {
        static::observe(ModelObserver::class);

        static::$cipherSweetEncryptedRow = new EncryptedRow(
            app(CipherSweetEngine::class),
            (new static)->getTable()
        );

        static::configureCipherSweetFields(static::$cipherSweetEncryptedRow);
        static::configureCipherSweetIndexes(static::$cipherSweetEncryptedRow);
        static::configureCipherSweet(static::$cipherSweetEncryptedRow);
    }

    /**
     * Configures which fields are encrypted and as what type. Additionally configures a source of additional
     * authenticated data.
     *
     * @param EncryptedRow $encryptedRow
     * @return void
     */
    private static function configureCipherSweetFields(EncryptedRow $encryptedRow)
    {
        foreach (static::$cipherSweetFields as $field => $type) {
            $aadSource = '';

            if (is_array($type)) {
                list($type, $aadSource) = $type;
            }

            $encryptedRow->addField($field, $type, $aadSource);
        }
    }

    /**
     * Configures blind indexes.
     *
     * @param EncryptedRow $encryptedRow
     * @return void
     */
    private static function configureCipherSweetIndexes(EncryptedRow $encryptedRow)
    {
        foreach (static::$cipherSweetIndexes as $index => $configuration) {
            $configuration = Arr::wrap($configuration);

            $column = $configuration[0];
            $transformations = isset($configuration[1]) ? static::convertTransformations(Arr::wrap($configuration[1])) : [];
            $isSlow = $configuration[2] ?? false;
            $filterBits = $configuration[3] ?? 256;
            $hashConfig = $configuration[4] ?? [];

            if (is_array($column)) {
                $compoundIndex = new CompoundIndex($index, $column, (int) $filterBits, !$isSlow, $hashConfig);

                foreach ($transformations as $transformation) {
                    $compoundIndex->addRowTransform($transformation);
                }

                $encryptedRow->addCompoundIndex($compoundIndex);
            } else {
                $encryptedRow->addBlindIndex($column, new BlindIndex($index, $transformations, (int) $filterBits, !$isSlow, $hashConfig));
            }

            static::$indexToField[$index] = $column;
        }
    }

    /**
     * @param array<string> $transformations
     * @return array<TransformationInterface>
     */
    private static function convertTransformations(array $transformations): array
    {
        return array_map(function ($transformation) {
            return app($transformation);
        }, $transformations);
    }

    /**
     * Override for additional configuration of the table's encrypted fields and indexes.
     *
     * @param EncryptedRow $encryptedRow
     * @return void
     */
    protected static function configureCipherSweet(EncryptedRow $encryptedRow)
    {
        //
    }

    /**
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\ArrayKeyException
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    public function encryptRow()
    {
        $this->setRawAttributes(static::$cipherSweetEncryptedRow->encryptRow($this->getAttributes()), true);
    }

    /**
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    public function decryptRow()
    {
        $this->setRawAttributes(static::$cipherSweetEncryptedRow->decryptRow($this->getAttributes()));
    }
}
