<?php

namespace Frontastic\Common\ProductApiBundle\Controller;

use Frontastic\Common\CoreBundle\Domain\ClassToCatwalkPackageMigrationHandler;

ClassToCatwalkPackageMigrationHandler::handleClass(
    'CategoryController',
    __NAMESPACE__,
    'Frontastic\Catwalk\FrontendBundle\Controller',
    'ProductCategoryController'
);
