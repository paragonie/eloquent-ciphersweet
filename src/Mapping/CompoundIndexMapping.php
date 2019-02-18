<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Mapping;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CompoundIndexMapping
 * @package ParagonIE\EloquentCipherSweet\Mapping
 */
class CompoundIndexMapping implements IndexMappingInterface
{
    /**
     * @var string $table
     */
    private $table = '';

    /**
     * @var string $index
     */
    private $index = '';

    /**
     * @var string $target
     */
    private $target = '';

    /**
     * @var string $property
     */
    private $property = '';

    /**
     * BlindIndexMapping constructor.
     *
     * @param string $table
     * @param string $index
     * @param Model|null $model
     * @param string $property
     */
    public function __construct(
        string $table = '',
        string $index = '',
        Model $model = null,
        string $property = ''
    ) {
        $this->table = $table;
        $this->index = $index;
        $this->target = $model;
        $this->property = $property;
    }

    /**
     * @param array $indexes
     */
    public function __invoke(array $indexes)
    {
        if (!isset($indexes[$this->table][$this->index]['value'])) {
            throw new \TypeError('Missing indexes on input array');
        }
        $this->target->{$this->property} = $indexes[$this->table][$this->index]['value'];
    }
}
