<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\EncryptedRow;

/**
 * Trait CipherSweet
 *
 * Makes integrating CipherSweet with Eloquent ORM much easier.
 *
 * @package ParagonIE\EloquentCipherSweet
 */
trait CipherSweet
{
    /** @var EncryptedRow */
    protected static $cipherSweetEncryptedRow;

    protected static $cipherSweetFields = [];

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
