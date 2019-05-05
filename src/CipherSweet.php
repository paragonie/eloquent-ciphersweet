<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\Constants;
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
    final protected static function bootCipherSweet()
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
     * @param  object|array|string  $classes
     * @return void
     * @throws \RuntimeException
     */
    abstract public static function observe($classes);

    /**
     * Configures which fields are encrypted and as what type. Additionally configures a source of additional
     * authenticated data.
     *
     * @param EncryptedRow $encryptedRow
     * @return void
     */
    final protected static function configureCipherSweetFields(EncryptedRow $encryptedRow)
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
    final protected static function configureCipherSweetIndexes(EncryptedRow $encryptedRow)
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
    final protected static function convertTransformations(array $transformations): array
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
    final public function encryptRow()
    {
        $this->setRawAttributes(static::$cipherSweetEncryptedRow->encryptRow($this->getAttributes()), true);
    }

    /**
     * @param array $attributes
     * @param bool $sync
     * @return $this
     */
    abstract public function setRawAttributes(array $attributes, $sync = false);

    /**
     * @return array
     */
    abstract public function getAttributes();

    /**
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    final public function decryptRow()
    {
        $this->setRawAttributes(static::$cipherSweetEncryptedRow->decryptRow($this->getAttributes()));
    }

    /**
     * @param EloquentBuilder $query
     * @param string $indexName
     * @param string|array<string,mixed> $value
     * @return EloquentBuilder
     */
    final public function scopeWhereBlind(EloquentBuilder $query, string $indexName, $value)
    {
        return $query->whereExists(function (Builder $query) use ($indexName, $value): Builder {
            /**
             * @var CipherSweetEngine $engine
             * @var $model Model|\ParagonIE\EloquentCipherSweet\CipherSweet
             */
            $engine = app(CipherSweetEngine::class);
            $table = $this->getTable();

            $column = static::$indexToField[$indexName];
            $columns = is_string($column) ? [$column => $value] : $value;

            return $query->select(DB::raw(1))
                ->from('blind_indexes')
                ->whereRaw(
                    'blind_indexes.foreign_id = ?.?',
                    [$table, $this->getKeyName()]
                )
                ->where(
                    'blind_indexes.type',
                    $engine->getIndexTypeColumn($table, is_string($column) ? $column : Constants::COMPOUND_SPECIAL, $indexName)
                )
                ->where(
                    'blind_indexes.value',
                    static::$cipherSweetEncryptedRow->getBlindIndex($indexName, $columns)
                );
        });
    }

    /**
     * @return string
     */
    abstract public function getTable();

    /**
     * @return string
     */
    abstract public function getKeyName();
}
