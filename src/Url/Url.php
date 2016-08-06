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
        // Remove all illegal characters from a url
        $this->url = filter_var(trim($url), FILTER_SANITIZE_URL);
        // Validate url
        if (filter_var($this->url, FILTER_VALIDATE_URL) !== false) {
            $this->valid = true;

            if (substr($this->url, 0, 4) !== 'http') {
                $this->url = 'http://' . ltrim($this->url, '/');
            }

            $base = parse_url($this->url, PHP_URL_HOST);
            if (substr($base, 0, 4) !== 'www.') {
                $base = 'www.' . $base;
            }

            $this->base = $base;
        }
    }

    /**
     * @return boolean
     */
    public function isValid(): bool
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
