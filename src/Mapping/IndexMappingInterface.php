<?php
declare(strict_types=1);
namespace ParagonIE\EloquentCipherSweet\Mapping;

/**
 * Interface IndexMappingInterface
 * @package ParagonIE\EloquentCipherSweet\Mapping
 */
interface IndexMappingInterface
{
    /**
     * @param array $indexes
     */
    public function __invoke(array $indexes);
}
