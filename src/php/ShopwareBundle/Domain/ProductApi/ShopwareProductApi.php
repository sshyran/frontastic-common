<?php declare(strict_types = 1);

namespace Frontastic\Common\ShopwareBundle\Domain\ProductApi;

use Frontastic\Common\ProductApiBundle\Domain\ProductApi;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\EnabledFacetService;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Query\CategoryQuery;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Query\ProductQuery;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Query\ProductTypeQuery;
use Frontastic\Common\ShopwareBundle\Domain\AbstractShopwareApi;
use Frontastic\Common\ShopwareBundle\Domain\ClientInterface;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\DataMapperInterface;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\DataMapperResolver;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\QueryAwareDataMapperInterface;
use Frontastic\Common\ShopwareBundle\Domain\Locale\LocaleCreator;
use Frontastic\Common\ShopwareBundle\Domain\ProductApi\DataMapper\CategoryMapper;
use Frontastic\Common\ShopwareBundle\Domain\ProductApi\DataMapper\ProductMapper;
use Frontastic\Common\ShopwareBundle\Domain\ProductApi\DataMapper\ProductResultMapper;
use Frontastic\Common\ShopwareBundle\Domain\ProductApi\Query\QueryFacetExpander;
use Frontastic\Common\ShopwareBundle\Domain\ProductApi\Search\SearchCriteriaBuilder;

class ShopwareProductApi extends AbstractShopwareApi implements ProductApi
{
    /**
     * @var \Frontastic\Common\ProductApiBundle\Domain\ProductApi\EnabledFacetService
     */
    private $enabledFacetService;

    /**
     * @var \Frontastic\Common\ProductApiBundle\Domain\ProductApi\Query
     */
    private $query;

    public function __construct(
        ClientInterface $client,
        DataMapperResolver $mapperResolver,
        LocaleCreator $localeCreator,
        string $defaultLanguage,
        EnabledFacetService $enabledFacetService
    ) {
        parent::__construct($client, $mapperResolver, $localeCreator, $defaultLanguage);

        $this->enabledFacetService = $enabledFacetService;
    }

    public function getCategories(CategoryQuery $query): array
    {
        $this->query = $query;

        $criteria = SearchCriteriaBuilder::buildFromCategoryQuery($query);

        $locale = $this->parseLocaleString($query->locale);

        return $this->client
            ->forLanguage($locale->languageId)
            ->post('/category', [], $criteria)
            ->then(function ($response) {
                return $this->mapResponse($response, CategoryMapper::MAPPER_NAME);
            })
            ->wait();
    }

    public function getProductTypes(ProductTypeQuery $query): array
    {
        return [];
    }

    public function getProduct($query, string $mode = self::QUERY_SYNC): ?object
    {
        $query = ProductApi\Query\SingleProductQuery::fromLegacyQuery($query);
        $query->validate();

        $this->query = $query;

        $criteria = SearchCriteriaBuilder::buildFromSimpleProductQuery($query);

        $locale = $this->parseLocaleString($query->locale);

        $promise = $this->client
            ->forCurrency($locale->currencyId)
            ->forLanguage($locale->languageId)
            ->post('/product', [], $criteria)
            ->then(function ($response) {
                return $this->mapResponse($response, ProductMapper::MAPPER_NAME);
            });

        if ($mode === self::QUERY_SYNC) {
            return $promise->wait();
        }

        return $promise;
    }

    public function query(ProductQuery $query, string $mode = self::QUERY_SYNC): object
    {
        $query = QueryFacetExpander::expandQueryEnabledFacets(
            $query,
            $this->enabledFacetService->getEnabledFacetDefinitions()
        );

        $this->query = $query;

        $criteria = SearchCriteriaBuilder::buildFromProductQuery($query);

        $locale = $this->parseLocaleString($query->locale);

        $promise = $this->client
            ->forCurrency($locale->currencyId)
            ->forLanguage($locale->languageId)
            ->post('/product', [], $criteria)
            ->then(function ($response) {
                return $this->mapResponse($response, ProductResultMapper::MAPPER_NAME);
            });

        if ($mode === self::QUERY_SYNC) {
            return $promise->wait();
        }

        return $promise;
    }

    public function getDangerousInnerClient(): ClientInterface
    {
        return $this->client;
    }

    protected function configureMapper(DataMapperInterface $mapper): void
    {
        if ($this->query !== null && $mapper instanceof QueryAwareDataMapperInterface) {
            $mapper->setQuery($this->query);
        }
    }
}
