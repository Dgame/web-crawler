<?php

namespace Doody\Crawler\Crawler;

use Doody\Crawler\Language\Language;
use Doody\Crawler\Mongo\MongoCollection;
use Doody\Crawler\Url\Url;

/**
 * Class DataRecorder
 * @package Doody\Crawler\Crawler
 */
final class DataRecorder
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @param Url    $parent
     * @param Url    $child
     * @param Filter $filter
     */
    public function append(Url $parent, Url $child, Filter $filter)
    {
        $this->records[] = [
            'url'      => $child->asString(),
            'base'     => $child->getBaseUrl(),
            'content'  => $filter->getContent(),
            'title'    => $filter->hasTitle() ? $filter->getTitle() : $child->getBaseUrl(),
            'language' => Language::Instance()->detectLanguage($filter->getContent()),
            'pr'       => 0,
            'in'       => [$parent->asString()],
        ];
    }

    /**
     *
     */
    public function apply()
    {
        if (DB_INSERT && !empty($this->records)) {
            MongoCollection::Instance('raw_pages')->getCollection()->insertMany($this->records);
            $this->records = [];
        }
    }
}