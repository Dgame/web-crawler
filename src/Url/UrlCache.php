<?php

namespace Doody\Crawler\Url;

/**
 * Class UrlCache
 * @package Doody\Crawler\Url
 */
final class UrlCache
{
    /**
     * @var array
     */
    private $shouldCrawlCache = [];
    /**
     * @var array
     */
    private $shouldInsertCache = [];

    /**
     * @param Url  $url
     * @param bool $result
     */
    public function cacheShouldCrawl(Url $url, bool $result)
    {
        if (!$result) {
            $this->shouldCrawlCache[$url->asString()] = true;
        }
    }

    /**
     * @param Relation $relation
     * @param bool     $result
     */
    public function cacheShouldInsert(Relation $relation, bool $result)
    {
        if (!$result) {
            $this->shouldInsertCache[$relation->asString()] = true;
        }
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    public function shouldCrawl(Url $url) : bool
    {
        if (array_key_exists($url->asString(), $this->shouldCrawlCache)) {
            return false;
        }

        return true;
    }

    /**
     * @param Relation $relation
     *
     * @return bool
     */
    public function shouldInsert(Relation $relation) : bool
    {
        if (array_key_exists($relation->asString(), $this->shouldInsertCache)) {
            return false;
        }

        return true;
    }
}