<?php

namespace Frontastic\Common\SprykerBundle\Domain\Product\Mapper;

use Frontastic\Common\ProductApiBundle\Domain\Product;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Result;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Result\Facet;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Result\RangeFacet;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Result\Term;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Result\TermFacet;
use Frontastic\Common\ProductApiBundle\Domain\Variant;
use Frontastic\Common\SprykerBundle\Domain\MapperInterface;
use Frontastic\Common\SprykerBundle\Domain\Product\SprykerSlugger;
use WoohooLabs\Yang\JsonApi\Schema\Resource\ResourceObject;

class ProductResultMapper implements MapperInterface
{
    public const MAPPER_NAME = 'product-result';

    protected const IMAGE_KEY = 'externalUrlSmall';

    /**
     * @param ResourceObject $resource
     * @return Result|mixed
     */
    public function mapResource(ResourceObject $resource)
    {
        $result = new Result();

        $pagination = $resource->attribute('pagination');

        $result->total = $pagination['numFound'];
        $result->count = $pagination['currentItemsPerPage'];
        $result->offset = max(0, $pagination['currentPage'] - 1) * $pagination['currentItemsPerPage'];

        $result->items = $this->mapProducts($resource);
        $result->facets = $this->mapFacets($resource);

        return $result;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::MAPPER_NAME;
    }

    /**
     * @param ResourceObject $resource
     * @return Product[]
     */
    private function mapProducts(ResourceObject $resource): array
    {
        $products = [];

        foreach ($resource->attribute('abstractProducts', []) as $abstractProduct) {
            $products[] = $this->mapProductFromArray($abstractProduct);
        }
        return $products;
    }

    /**
     * @param array $abstractProduct
     * @return Product
     */
    private function mapProductFromArray(array $abstractProduct): Product
    {
        $images = array_map(static function (array $image) {
            return $image[static::IMAGE_KEY];
        }, $abstractProduct['images']);

        $variant = new Variant();
        $variant->sku = (string)$abstractProduct['abstractSku'];
        $variant->id = $abstractProduct['abstractSku'];
        $variant->price = $abstractProduct['price'];
        $variant->images = $images;
        $variant->dangerousInnerVariant = $abstractProduct;

        $product = new Product();
        $product->name = $abstractProduct['abstractName'];
        $product->productId = $abstractProduct['abstractSku'];
        $product->slug = SprykerSlugger::slugify($abstractProduct['abstractName']);
        $product->variants = [$variant];
        $product->dangerousInnerProduct = $abstractProduct;

        return $product;
    }

    /**
     * @param ResourceObject $resource
     * @return Facet[]
     */
    private function mapFacets(ResourceObject $resource): array
    {
        $facets = [];

        foreach ($resource->attribute('rangeFacets', []) as $rangeFacet) {
            $parameterName = $rangeFacet['config']['parameterName'];

            $facet = new RangeFacet();
            $facet->key = $parameterName;
            $facet->handle = $parameterName;
            $facet->min = $rangeFacet['min'];
            $facet->max = $rangeFacet['max'];

            $facets[] = $facet;
        }

        foreach ($resource->attribute('valueFacets', []) as $valueFacet) {
            $parameterName = $valueFacet['config']['parameterName'];

            $facet = new TermFacet();
            $facet->key = $parameterName;
            $facet->handle = $parameterName;

            $facet->terms = array_map(function ($value) use ($parameterName) {
                $term = new Term();
                $term->name = $value['value'];
                $term->handle = $value['value'];
                $term->value = $value['value'];
                $term->count = $value['doc_count'];

                return $term;
            }, $valueFacet['values'] ?? []);

            $facets[] = $facet;
        }

        return $facets;
    }
}
