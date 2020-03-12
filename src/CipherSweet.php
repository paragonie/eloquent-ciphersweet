<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet as CipherSweetEngine;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\Constants;
use ParagonIE\CipherSweet\Contract\TransformationInterface;
use ParagonIE\CipherSweet\EncryptedField;

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
    /**
     * @var array
     */
    protected static $cipherSweetEncryptedFields = [];

    /**
     * @var array
     */
    protected static $cipherSweetFields = [];

    /**
     * @var array
     */
    protected static $cipherSweetIndexes = [];

    /**
     * @var array
     */
    private static $indexToField = [];

    /**
     * @return void
     */
    final protected static function bootCipherSweet()
    {
        static::observe(ModelObserver::class);

        static::configureCipherSweetFields();
        static::configureCipherSweetIndexes();
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
     * @return void
     */
    final protected static function configureCipherSweetFields()
    {
        if (isset(static::$encrypted)) {
            static::$cipherSweetFields = static::$encrypted;
        }

        foreach (static::$cipherSweetFields as $field => $type) {
            $aadSource = '';

            if (is_array($type)) {
                list($type, $aadSource) = $type;
            }

            static::$cipherSweetEncryptedFields[$field] = new EncryptedField(
                app(CipherSweetEngine::class),
                (new static)->getTable(),
                $field
            );
        }
    }

    /**
     * Configures blind indexes.
     *
     * @return void
     */
    final protected static function configureCipherSweetIndexes()
    {
        if (isset(static::$encryptedIndexes)) {
            static::$cipherSweetIndexes = static::$encryptedIndexes;
        }

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

//                $encryptedRow->addCompoundIndex($compoundIndex);
            } else {
                $encryptedField = static::$cipherSweetEncryptedFields[$column];
                $encryptedField->addBlindIndex(new BlindIndex($index, $transformations, (int) $filterBits, !$isSlow, $hashConfig));
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
     * Get the configuration setting for the prefix used to determine if a string is encrypted.
     *
     * @return string
     */
    protected function getEncryptionPrefix(): string
    {
        return config('ciphersweet.backend');
    }

    /**
     * Determine whether an attribute should be encrypted.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function shouldEncrypt($key): bool
    {
        $encrypt = isset(static::$encrypted) && is_array(static::$encrypted) ? static::$encrypted : [];

        return array_key_exists($key, $encrypt);
    }

    /**
     * Determine whether a model is ready for encryption.
     *
     * @return bool
     */
    protected function isEncryptable(): bool
    {
        $exists = property_exists($this, 'exists');

        return $exists === false || ($exists === true && $this->exists === true);
    }

    /**
     * Determine whether a string has already been encrypted.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isEncrypted($value): bool
    {
        return strpos((string) $value, $this->getEncryptionPrefix()) === 0;
    }

    /**
     * Return the encrypted value of an attribute's value.
     *
     * This has been exposed as a public method because it is of some
     * use when searching.
     *
     * @param string $value
     *
     * @return null|string
     */
    public function encryptedAttribute($key, $value): ?string
    {
        $encryptedField = static::$cipherSweetEncryptedFields[$key];

        // Check if string is null
        if (static::$cipherSweetFields[$key] === 'string' && $value === null) {
            return $encryptedField->encryptValue('');
        }

        // Check if attribute is double\float
        if (static::$cipherSweetFields[$key] === 'string' && is_float($value)) {
            return $encryptedField->encryptValue(strval($value));
        }

        return $encryptedField->encryptValue($value);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function decryptedAttribute($key, $value)
    {
        $encryptedField = static::$cipherSweetEncryptedFields[$key];

        return $encryptedField->decryptValue($value);
    }

    /**
     * Encrypt a stored attribute.
     *
     * @param string $key
     *
     * @return self
     */
    protected function doEncryptAttribute($key): self
    {
        if ($this->shouldEncrypt($key) && ! $this->isEncrypted($this->attributes[$key])) {
            try {
                $this->attributes[$key] = $this->encryptedAttribute($key, $this->attributes[$key]);
            } catch (EncryptException $exception) {
                $this->setLastEncryptionException($exception, __FUNCTION__);
            }
        }

        return $this;
    }

    /**
     * Decrypt an attribute if required.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function doDecryptAttribute($key, $value)
    {
        if ($this->shouldEncrypt($key) && $this->isEncrypted($value)) {
            try {
                return $this->decryptedAttribute($key, $value);
            } catch (DecryptException $exception) {
                $this->setLastEncryptionException($exception, __FUNCTION__);
            }
        }

        return $value;
    }

    /**
     * Decrypt each attribute in the array as required.
     *
     * @param array $attributes
     *
     * @return array
     */
    public function doDecryptAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $this->doDecryptAttribute($key, $value);
        }

        return $attributes;
    }

    /**
     * Decrypt encrypted data before it is processed by cast attribute.
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        return parent::castAttribute($key, $this->doDecryptAttribute($key, $value));
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!$this->originalIsEquivalent($key, $value)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setAttribute($key, $value)
    {
        parent::setAttribute($key, $value);

        $this->doEncryptAttribute($key);
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        return $this->doDecryptAttribute($key, parent::getAttributeFromArray($key));
    }

    /**
     * Get an attribute array of all arrayable attributes.
     *
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->doDecryptAttributes(parent::getArrayableAttributes());
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->isEncryptable() ? $this->doDecryptAttributes(parent::getAttributes()) : parent::getAttributes();
    }

    /**
     * @return void
     */
    final public function saveBlindIndexes()
    {
        /**
         * @var CipherSweetEngine
         */
        $engine = app(CipherSweetEngine::class);
        $table = $this->getTable();
        $attributes = $this->getAttributes();

        foreach (static::$indexToField as $indexName => $field) {
            $encryptedField = static::$cipherSweetEncryptedFields[$field];

            if (isset($attributes[$field])) {
                $blindIndexType = $engine->getIndexTypeColumn($table, is_string($field) ? $field : Constants::COMPOUND_SPECIAL, $indexName);
                $blindIndexValue = $encryptedField->getBlindIndex($attributes[$field], $indexName);

                DB::table('blind_indexes')->updateOrInsert([
                    'type' => $blindIndexType,
                    'foreign_id' => $this->getKey()
                ], [
                    'value' => $blindIndexValue,
                ]);
            }
        }
    }

    /**
     * @param EloquentBuilder $query
     * @param string $indexName
     * @param string|array<string,mixed> $value
     * @return EloquentBuilder
     */
    final public static function scopeWhereBlind(EloquentBuilder $query, string $indexName, $value)
    {
        return $query->whereExists(function (Builder $query) use ($indexName, $value): Builder {
            if ($value === null) {
                return $query;
            }

            $engine = app(CipherSweetEngine::class);
            $table = (new static)->getTable();
            $keyName = (new static)->getKeyName();

            $column = static::$indexToField[$indexName];
            $columns = is_string($column) ? [$column => $value] : $value;
            $encryptedField = static::$cipherSweetEncryptedFields[$column];

            return $query->select(DB::raw(1))
                ->from('blind_indexes')
                ->whereRaw("blind_indexes.foreign_id = {$table}.{$keyName}")
                ->where(
                    'blind_indexes.type',
                    $engine->getIndexTypeColumn($table, is_string($column) ? $column : Constants::COMPOUND_SPECIAL, $indexName)
                )
                ->where(
                    'blind_indexes.value',
                    $encryptedField->getBlindIndex($value, $indexName)
                );
        });
    }

    /**
     * @param EloquentBuilder $query
     * @param string $indexName
     * @param $value
     * @return EloquentBuilder|Builder
     */
    final public static function scopeOrWhereBlind(EloquentBuilder $query, string $indexName, $value)
    {
        return $query->orWhereExists(function (Builder $query) use ($indexName, $value): Builder {
            if ($value === null) {
                return $query;
            }

            $engine = app(CipherSweetEngine::class);
            $table = (new static)->getTable();
            $keyName = (new static)->getKeyName();

            $column = static::$indexToField[$indexName];
            $columns = is_string($column) ? [$column => $value] : $value;
            $encryptedField = static::$cipherSweetEncryptedFields[$column];

            return $query->select(DB::raw(1))
                ->from('blind_indexes')
                ->whereRaw("blind_indexes.foreign_id = {$table}.{$keyName}")
                ->where(
                    'blind_indexes.type',
                    $engine->getIndexTypeColumn($table, is_string($column) ? $column : Constants::COMPOUND_SPECIAL, $indexName)
                )
                ->where(
                    'blind_indexes.value',
                    $encryptedField->getBlindIndex($value, $indexName)
                );
        });
    }

    /**
     * @param EloquentBuilder $query
     * @param string $indexName
     * @param $valueArray
     * @return EloquentBuilder
     */
    public static function scopeWhereInBlind(EloquentBuilder $query, string $indexName, $valueArray)
    {
        // Validate $valueArray
        if (!is_array($valueArray)) {
            return $query;
        }

        foreach ($valueArray as $key => $value) {
            if ($key === 0) {
                $query->whereBlind($indexName, $value);
            } else {
                $query->orWhereBlind($indexName, $value);
            }
        }

        return $query;
    }

    /**
     * @return string
     */
    abstract public function getTable();

    /**
     * @return string
     */
    abstract public function getKeyName();

    /**
     * @return array
     * @throws \SodiumException
     */
    final public static function getBlindIndexTypes(): array
    {
        /** @var CipherSweetEngine $engine */
        $engine = app(CipherSweetEngine::class);
        $table = (new static)->getTable();

        $types = [];

        foreach (static::$indexToField as $indexName => $field) {
            $types[] = $engine->getIndexTypeColumn($table, is_string($field) ? $field : Constants::COMPOUND_SPECIAL, $indexName);
        }

        return $types;
    }
}
