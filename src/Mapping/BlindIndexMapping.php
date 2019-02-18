<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Mapping;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BlindIndexMapping
 * @package ParagonIE\EloquentCipherSweet\Mapping
 */
class BlindIndexMapping implements IndexMappingInterface
{
    /**
     * @var string $table
     */
    private $table = '';

    /**
     * @var string $column
     */
    private $column = '';

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
     * @param string $column
     * @param string $index
     * @param Model|null $model
     * @param string $property
     */
    public function __construct(
        string $table = '',
        string $column = '',
        string $index = '',
        Model $model = null,
        string $property = ''
    ) {
        $this->table = $table;
        $this->column = $column;
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
