<?php

namespace Doody\Crawler\Url;

/**
 * Class Url
 * @package Doody\Crawler
 */
final class Url
{
    /**
     * @var string
     */
    private $url = '';
    /**
     * @var string
     */
    private $base = '';
    /**
     * @var bool
     */
    private $valid = false;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $base = parse_url($url, PHP_URL_HOST);
        if (!empty($base)) {
            if (substr($base, 0, 4) !== 'www.') {
                $base = 'www.' . $base;
            }

            $this->base = $base;
            $this->url  = trim($url);
            if (substr($this->url, 0, 4) !== 'http') {
                $this->url = 'http://' . ltrim($this->url, '/');
            }

            $this->valid = true;
        }
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
