<?php

namespace Doody\Crawler\Url;

/**
 * Class RelationProcedure
 * @package Doody\Crawler\Url
 */
final class RelationProcedure
{
    use RelationTrait;

    /**
     * RelationProcedure constructor.
     *
     * @param Url $url
     */
    private function __construct(Url $url)
    {
        $this->parent = $url;
    }

    /**
     * @param Url $url
     *
     * @return RelationProcedure
     */
    public static function Link(Url $url) : RelationProcedure
    {
        return new self($url);
    }

    /**
     * @param Url $url
     *
     * @return Relation
     */
    public function with(Url $url) : Relation
    {
        $this->child = $url;

        return new Relation($this);
    }
}