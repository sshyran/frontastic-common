<?php declare(strict_types = 1);

namespace Frontastic\Common\ShopwareBundle\Domain\ProductApi\DataMapper;

use Frontastic\Common\ShopwareBundle\Domain\DataMapperInterface;
use Frontastic\Common\ShopwareBundle\Domain\QueryAwareDataMapperInterface;

abstract class AbstractDataMapper implements DataMapperInterface
{
    private const KEY_DATA = 'data';
    private const KEY_AGGREGATIONS = 'aggregations';

    protected function extractData(array $resource, array $fallback = []): array
    {
        return $resource[self::KEY_DATA] ?? $fallback;
    }

    protected function extractAggregations(array $resource): array
    {
        return $resource[self::KEY_AGGREGATIONS] ?? [];
    }

    protected function mapDangerousInnerData(array $innerData): ?array
    {
        if (!$this instanceof QueryAwareDataMapperInterface || $this->getQuery()->loadDangerousInnerData) {
            return null;
        }

        return $innerData;
    }
}