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
     * @var null|bool
     */
    private $valid = null;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $url = trim($url);
        if (substr($url, 0, 4) !== 'http') {
            $url = 'http://' . ltrim($url, '/');
        }

        $this->url   = $url;
        $this->valid = filter_var($url, FILTER_VALIDATE_URL) !== false && strpos($url, 'mailto') === false;

        $base = parse_url($url, PHP_URL_HOST);
        if (substr($base, 0, 4) !== 'www.') {
            $base = 'www.' . $base;
        }

        $this->base = $base;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->valid;
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
