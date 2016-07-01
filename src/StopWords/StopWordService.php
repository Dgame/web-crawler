<?php

namespace Doody\Crawler\StopWords;

use Doody\Crawler\Url;

/**
 * Class StopWords
 * @package Doody\Crawler\StopWords
 */
final class StopWordService
{
    const LANGUAGE_PATTERN = [
        Language::DE => '#(?:de\.|\.de)#'
    ];

    /**
     * @var StopWordService
     */
    private static $instance = null;
    /**
     * @var StopWords[]
     */
    private $languages = [];

    /**
     * Lexer constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return StopWordService
     */
    public static function Instance() : StopWordService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Url $url
     *
     * @return Language
     * @throws \Exception
     */
    public function detectLanguageFromURL(Url $url) : Language
    {
        foreach (self::LANGUAGE_PATTERN as $language => $pattern) {
            if ($url->match($pattern)) {
                return Language::Load($language);
            }
        }

        return Language::EN();
    }

    /**
     * @param Language $language
     *
     * @return StopWords
     */
    public function loadLanguage(Language $language) : StopWords
    {
        $lang = $language->getLanguageShortcut();
        if (!array_key_exists($lang, $this->languages)) {
            $this->languages[$lang] = new StopWords($language);
        }

        return $this->languages[$lang];
    }

    /**
     * @param Url $url
     *
     * @return StopWords
     */
    public function loadLanguageByURL(Url $url) : StopWords
    {
        $language = $this->detectLanguageFromURL($url);

        return $this->loadLanguage($language);
    }
}