<?php

namespace Frontastic\Common\ContentApiBundle\Domain\ContentApi;

use Kore\DataObject\DataObject;

/**
 * @type
 */
class Content extends DataObject
{
    /**
     * @var string
     */
    public $contentId;

    /**
     * @var string
     */
    public $contentTypeId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $slug;

    /**
     * @var Attribute[]
     */
    public $attributes = [];

    /**
     * Access original object from backend
     *
     * This should only be used if you need very specific features
     * right NOW. Please notify Frontastic about your need so that
     * we can integrate those twith the common API. Any usage off
     * this property might make your code unstable against future
     * changes.
     *
     * @var mixed
     */
    public $dangerousInnerContent;
}
