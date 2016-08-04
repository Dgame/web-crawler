<?php

namespace Doody\Crawler\Url;

/**
 * Class RelationTrait
 * @package Doody\Crawler\Url
 */
trait RelationTrait
{
    /**
     * @var null|Url
     */
    private $parent = null;
    /**
     * @var null|Url
     */
    private $child = null;

    /**
     * @return Url
     */
    final public function getParent() : Url
    {
        return $this->parent;
    }

    /**
     * @return Url
     */
    final public function getChild() : Url
    {
        return $this->child;
    }
}