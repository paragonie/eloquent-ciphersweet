<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use RuntimeException;

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
    protected static EncryptedMultiRows $cipherSweetEncryptedMultiRows;

    final protected static function bootCipherSweet(): void
    {
        static::observe(ModelObserver::class);

        static::$cipherSweetEncryptedMultiRows = new EncryptedMultiRows(
            app(CipherSweetEngine::class)
        );

        static::configureCipherSweet(static::$cipherSweetEncryptedMultiRows);
    }

    /**
     * @param  object|array|string  $classes
     * @return void
     * @throws RuntimeException
     */
    abstract public static function observe($classes);

    /**
     * Override for additional configuration of the table's encrypted fields and indexes.
     *
     * @param EncryptedMultiRows $multiRows
     * @return void
     */
    protected static function configureCipherSweet(EncryptedMultiRows $multiRows)
    {
        // To be implemented by the user
    }

    /**
     * @return void
     * @throws \ParagonIE\CipherSweet\Exception\ArrayKeyException
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    final public function encryptRow()
    {
        $tableName = $this->getTable();
        $encryptedRow = static::$cipherSweetEncryptedMultiRows
            ->getEncryptedRowObjectForTable($tableName);

        $attributes = $this->getAttributes();

        $encrypted = $encryptedRow->encryptRow($attributes);
        $indexes = $encryptedRow->getAllBlindIndexes($attributes);

        $this->setRawAttributes(array_merge($encrypted, $indexes));
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
        $tableName = $this->getTable();
        $this->setRawAttributes(
            static::$cipherSweetEncryptedMultiRows
                ->getEncryptedRowObjectForTable($tableName)
                ->decryptRow($this->getAttributes()),
            true
        );
    }

    /**
     * @param EloquentBuilder $query
     * @param string $column
     * @param string $value
     * @param string $indexName
     * @return EloquentBuilder
     * @throws \ParagonIE\CipherSweet\Exception\BlindIndexNotFoundException
     * @throws \ParagonIE\CipherSweet\Exception\CryptoOperationException
     * @throws \SodiumException
     */
    final public function scopeWhereBlind(EloquentBuilder $query, string $column, string $value, string $indexName = ''): EloquentBuilder
    {
        $tableName = $this->getTable();
        if (empty($indexName)) {
            $indexName = $column . '_bi';
        }

        $biValue = static::$cipherSweetEncryptedMultiRows->getBlindIndex(
            $tableName,
            $indexName,
            [$column => $value]
        );

        return $query->where($indexName, $biValue);
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
