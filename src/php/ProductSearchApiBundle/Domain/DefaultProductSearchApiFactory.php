<?php

namespace Frontastic\Common\ProductSearchApiBundle\Domain;

use Frontastic\Common\FindologicBundle\Domain\FindologicClientFactory;
use Frontastic\Common\FindologicBundle\Domain\ProductSearchApi\FindologicProductSearchApi;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Commercetools\ClientFactory as CommercetoolsClientFactory;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Commercetools\Locale\CommercetoolsLocaleCreatorFactory;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Commercetools\Mapper as CommercetoolsDataMapper;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\EnabledFacetService;
use Frontastic\Common\ProductSearchApiBundle\Domain\ProductSearchApi\Commercetools as CommercetoolsProductSearchApi;
use Frontastic\Common\FindologicBundle\Domain\ProductSearchApi\Mapper as FindologicDataMapper;
use Frontastic\Common\ReplicatorBundle\Domain\Project;
use Psr\Container\ContainerInterface;

class DefaultProductSearchApiFactory implements ProductSearchApiFactory
{
    private const CONFIGURATION_TYPE_NAME = 'product';

    /** @var ContainerInterface */
    private $container;

    /** @var EnabledFacetService */
    private $enabledFacetService;

    /** @var array */
    private $decorators;

    public function __construct(
        ContainerInterface $container,
        EnabledFacetService $enabledFacetService,
        iterable $decorators = []
    ) {
        $this->container = $container;
        $this->enabledFacetService = $enabledFacetService;
        $this->decorators = $decorators;
    }

    public function factor(Project $project): ProductSearchApi
    {
        $productConfig = $project->getConfigurationSection(self::CONFIGURATION_TYPE_NAME);

        switch ($productConfig->engine) {
            case 'commercetools':
                $clientFactory = $this->container->get(CommercetoolsClientFactory::class);
                $dataMapper = $this->container->get(CommercetoolsDataMapper::class);
                $localeCreatorFactory = $this->container->get(CommercetoolsLocaleCreatorFactory::class);

                $client = $clientFactory->factorForProjectAndType($project, self::CONFIGURATION_TYPE_NAME);

                $productSearchApi = new CommercetoolsProductSearchApi(
                    $client,
                    $dataMapper,
                    $localeCreatorFactory->factor($project, $client),
                    $this->enabledFacetService,
                    $project->languages,
                    $project->defaultLanguage
                );
                break;
            case 'findologic':
                $clientFactory = $this->container->get(FindologicClientFactory::class);
                $dataMapper = $this->container->get(FindologicDataMapper::class);
                $client = $clientFactory->factorForProjectAndType($project, self::CONFIGURATION_TYPE_NAME);

                $productSearchApi = new FindologicProductSearchApi($client, new NoopProductSearchApi(), $dataMapper);
                break;
            default:
                throw new \OutOfBoundsException(
                    "No product search API configured for project {$project->name}. " .
                    "Check the provisioned customer configuration in app/config/customers/."
                );
        }

        return new LifecycleEventDecorator($productSearchApi, $this->decorators);
    }
}
