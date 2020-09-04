<?php

namespace Frontastic\Common\FindologicBundle\Domain\ProductSearchApi;

use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Query\ProductQuery;

class QueryValidator
{
    public function isSupported(ProductQuery $query)
    {
        $hasMultipleSortFields = !empty($query->sortAttributes)
            && is_array($query->sortAttributes)
            && count($query->sortAttributes) > 1;

        if ($hasMultipleSortFields) {
            return ValidationResult::createUnsupported('Sorting by more than one field is not supported.');
        }

        if (!empty($query->sku) || !empty($query->skus)) {
            return ValidationResult::createUnsupported('Searching for SKUs is not supported.');
        }

        if (!empty($query->productIds) && count($query->productIds) > 1) {
            return ValidationResult::createUnsupported('Searching for multiple productIds is not supported.');
        }

        $hasCriteria = !empty($query->query) || !empty($query->facets) || !empty($query->filter);
        $hasIds = !empty($query->productIds) || !empty($query->productId);

        if ($hasCriteria && $hasIds) {
            return ValidationResult::createUnsupported(
                'Searching for productIds with additional query parameters is not supported'
            );
        }

        return ValidationResult::createValid();
    }
}
