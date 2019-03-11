<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\EncryptedMultiRows;
use ParagonIE\CipherSweet\Exception\ArrayKeyException;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use ParagonIE\EloquentCipherSweet\Mapping\IndexMappingInterface;

/**
 * Trait CipherSweet
 *
 * Makes integrating CipherSweet with Eloquent ORM much easier.
 *
 * @package ParagonIE\EloquentCipherSweet
 *
 * @method addGlobalScope(Scope $scope): void
 */
trait CipherSweet
{
    /** @var array<string, string> */
    public $cipherSweetColumnMap = [];

    // /** @var array<string, array<string, array<string, string>> */
    /** @var array<string, array<string, string>> */
    public $cipherSweetBlindIndexMap = [];

    /** @var array<string, string> */
    public $cipherSweetTableMap = [];

    /**
     * @return void
     */
    public static function bootCipherSweet()
    {
        static::addGlobalScope(new CipherSweetScope);
    }

    /**
     * Override me.
     *
     * @param EncryptedMultiRows $multiRows
     * @return EncryptedMultiRows
     */
    public function configureCipherSweet(EncryptedMultiRows $multiRows): EncryptedMultiRows
    {
        return $multiRows;
    }

    /**
     * Process all of the decryption after loading.
     * @return self
     *
     * @throws CryptoOperationException
     * @throws \SodiumException
     */
    public function decryptAfterLoad()
    {
        /** @var EncryptedMultiRows $processor */
        $processor = $this->getProcessor();

        // Prepare plaintext in structured array:
        $ciphertext = [];
        $this->mapModelsToArray($processor, $ciphertext);

        // Decrypt in place
        $plaintext = $processor->decryptManyRows($ciphertext);

        // Repopulate the models
        $this->mapArrayToModels($processor, $plaintext);

        return $this;
    }

    /**
     * Process all of the encryption and blind indexes before saving.
     * @return self
     *
     * @throws ArrayKeyException
     * @throws CryptoOperationException
     * @throws \SodiumException
     */
    public function encryptBeforeSave()
    {
        /** @var EncryptedMultiRows $processor */
        $processor = $this->getProcessor();

        // Prepare plaintext in structured array:
        $this->mapModelsToArray($processor, $plaintext);

        // Encrypt in-place
        list($ciphertext, $indexes) = $this->getProcessor()->prepareForStorage($plaintext);

        // Repopulate the models with ciphertext
        $this->mapArrayToModels($processor, $ciphertext);

        // Populate the appropriate models with the blind index values
        $this-> populateBlindIndexes($indexes);

        return $this;
    }

    /**
     * @return IndexMappingInterface[]
     */
    public function getCipherSweetBlindIndexMap()
    {
        return $this->cipherSweetBlindIndexMap;
    }

    /**
     * @return array
     */
    public function getCipherSweetColumnMap()
    {
        return $this->cipherSweetColumnMap;
    }

    /**
     * @param string $table
     * @return Model
     */
    protected function getCipherSweetModelForTable(string $table): Model
    {
        if (isset($this->cipherSweetTableMap[$table])) {
            // return $this->cipherSweetTableMap[$table];
        }

        // Failure case: We're the model
        if ($this instanceof Model) {
            return $this;
        } else {
            throw new \TypeError('This should only be used within an Eloquent model');
        }
    }

    /**
     * Return the "processor" object (which CipherSweet calls EncryptedMultiRows).
     *
     * We're using EncryptedMultiRows instead of EncryptedRow because Eloquent
     * supports many different relationships.
     *
     * @return EncryptedMultiRows
     */
    public function getProcessor(): EncryptedMultiRows
    {
        /** @var CipherSweetEngine $engine */
        $engine = app(CipherSweetEngine::class);

        /** @var EncryptedMultiRows $eMultiRow */
        $eMultiRow = new EncryptedMultiRows($engine);
        return $this->configureCipherSweet($eMultiRow);
    }

    /**
     * @param EncryptedMultiRows $processor
     * @param array $array
     */
    protected function mapArrayToModels(EncryptedMultiRows $processor, array &$array)
    {
        foreach ($processor->listTables() as $table) {
            /** @var EncryptedFieldModel $modelForTable */
            $modelForTable = $this->getCipherSweetModelForTable($table);

            $columnMap = [];
            if (method_exists($modelForTable, 'getCipherSweetColumnMap')) {
                $columnMap = $modelForTable->getCipherSweetColumnMap();
            }
            $eRow = $processor->getEncryptedRowObjectForTable($table);

            $array[$table] = [];
            foreach ($eRow->listEncryptedFields() as $field) {
                $property = isset($columnMap[$field]) ? $columnMap[$field] : $field;
                $modelForTable->{$property} = $array[$table][$field];
            }
        }
    }

    /**
     * @param EncryptedMultiRows $processor
     * @param array $array
     */
    protected function mapModelsToArray(EncryptedMultiRows $processor, array &$array)
    {
        foreach ($processor->listTables() as $table) {
            /** @var EncryptedFieldModel $modelForTable */
            $modelForTable = $this->getCipherSweetModelForTable($table);

            $columnMap = [];
            if (method_exists($modelForTable, 'getCipherSweetColumnMap')) {
                $columnMap = $modelForTable->getCipherSweetColumnMap();
            }
            $eRow = $processor->getEncryptedRowObjectForTable($table);

            $array[$table] = [];
            foreach ($eRow->listEncryptedFields() as $field) {
                $property = isset($columnMap[$field]) ? $columnMap[$field] : $field;
                $array[$table][$field] = $modelForTable->{$property};
            }
        }
    }

    /**
     * @param array $indexes
     */
    protected function populateBlindIndexes(array $indexes)
    {
        $allMappings = $this->getCipherSweetBlindIndexMap();
        foreach ($allMappings as $mapping) {
            $mapping($indexes);
        }
    }
}
