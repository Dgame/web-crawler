<?php

namespace Doody\Crawler\Url;

/**
 * Class UrlGuardian
 * @package Doody\Crawler\Url
 */
use Doody\Crawler\Mongo\Mongo;

/**
 * Class UrlGuardian
 * @package Doody\Crawler\Url
 */
final class UrlGuardian
{
    /**
     * @var null|UrlGuardian
     */
    private static $instance = null;

    /**
     * UrlGuardian constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return UrlGuardian
     */
    public static function Instance() : UrlGuardian
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    public function shouldCrawl(Url $url): bool
    {
        return $url->isValid() && $this->countChildsOf($url) === 0;
    }

    /**
     * @param Relation $relation
     *
     * @return bool
     */
    public function shouldInsert(Relation $relation): bool
    {
        return $relation->isValid() && $this->countRelation($relation) === 0;
    }

    /**
     * @param Url $url
     *
     * @return int
     */
    public function countChildsOf(Url $url): int
    {
        return Mongo::Instance()->getCollection()->count(
            [
                'in' => [
                    '$elemMatch' => [
                        '$eq' => $url->getBaseUrl()
                    ]
                ]
            ]
        );
    }

    /**
     * @param Relation $relation
     *
     * @return int
     */
    public function countRelation(Relation $relation): int
    {
        return Mongo::Instance()->getCollection()->count(
            [
                'url' => $relation->getChild()->asString(),
                'in'  => [
                    '$elemMatch' => [
                        '$eq' => $relation->getParent()->getBaseUrl()
                    ]
                ]
            ]
        );
    }
}