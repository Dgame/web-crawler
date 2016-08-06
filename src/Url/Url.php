<?php

namespace Doody\Crawler\Url;

/**
 * Class Url
 * @package Doody\Crawler
 */
final class Url
{
    /**
     * @var null|string
     */
    private $url = null;
    /**
     * @var null|string
     */
    private $base = null;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = trim($url);
        if (substr($this->url, 0, 4) !== 'http') {
            $this->url = 'http://' . ltrim($this->url, '/');
        }

        $base = parse_url($this->url, PHP_URL_HOST);
        if (substr($base, 0, 4) !== 'www.') {
            $base = 'www.' . $base;
        }

        $this->base = $base;
    }

    /**
     * @return string
     */
    public function asString() : string
    {
        return $this->url;
    }

    /**
     * Return the host part of an url
     * @return string
     */
    public function getBaseUrl() : string
    {
        return $this->base;
    }
}
