<?php

namespace Doody\Crawler\Url;

/**
 * Class Relation
 * @package Doody\Crawler\Url
 */
final class Relation
{
    use RelationTrait;

    /**
     * Relation constructor.
     *
     * @param RelationProcedure $procedure
     */
    public function __construct(RelationProcedure $procedure)
    {
        $this->parent = $procedure->getParent();
        $this->child  = $procedure->getChild();
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->parent->isValid() && $this->child->isValid();
    }

    /**
     * @return string
     */
    public function asString() : string
    {
        return sprintf('%s:%s', $this->parent->asString(), $this->child->asString());
    }
}
