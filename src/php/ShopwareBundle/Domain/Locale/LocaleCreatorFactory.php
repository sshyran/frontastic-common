<?php declare(strict_types = 1);

namespace Frontastic\Common\ShopwareBundle\Domain\Locale;

use Frontastic\Common\ReplicatorBundle\Domain\Project;

abstract class LocaleCreatorFactory
{
    abstract public function factor(Project $project): DefaultLocaleCreator;
}
