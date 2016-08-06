<?php

namespace Doody\Crawler\Http;

/**
 * Class HttpDispatcher
 * @package Doody\Crawler\Http
 */
final class HttpDispatcher
{
    const DEFAULT_OPTIONS = [
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR    => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH       => CURLAUTH_ANY,
        CURLOPT_SSLVERSION     => CURL_SSLVERSION_DEFAULT,
        CURLOPT_MAXREDIRS      => 5
    ];

    /**
     * @var null|resource
     */
    private $master = null;

    /**
     * HttpDispatcher constructor.
     */
    public function __construct()
    {
        $this->master = curl_multi_init();
    }

    /**
     *
     */
    public function __destruct()
    {
        curl_multi_close($this->master);
    }

    /**
     * @return array
     */
    private function readInfo() : array
    {
        $info = curl_multi_info_read($this->master);
        if ($info === false) {
            return [];
        }

        return $info;
    }

    /**
     * @param string $url
     */
    private function spawn(string $url)
    {
        $options = [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH       => CURLAUTH_ANY,
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_DEFAULT,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_URL            => $url
        ];

        $ch = curl_init();

        curl_setopt_array($ch, $options);
        curl_multi_add_handle($this->master, $ch);
    }

    /**
     * @param array    $urls
     * @param callable $callback
     */
    public function dispatch(array $urls, callable $callback)
    {
        $url_amount   = count($urls);
        $batch_amount = min($url_amount, 8);
        // start the first batch of requests
        for ($i = 0; $i < $batch_amount; $i++) {
            $this->spawn($urls[$i]);
        }

        do {
            while (($result = curl_multi_exec($this->master, $running)) === CURLM_CALL_MULTI_PERFORM) {
            }

            if ($result !== CURLM_OK) {
                break;
            }

            // a request was just completed -- find out which one
            while ($done = $this->readInfo()) {
                $info = curl_getinfo($done['handle']);
                if ($info['http_code'] === 200) {
                    $output = curl_multi_getcontent($done['handle']);

                    // request successful.  process output using the callback function.
                    $callback($info, $output);

                    if ($i < $url_amount) {
                        $this->spawn($urls[$i++]);
                    }

                    // remove the curl handle that just completed
                    curl_multi_remove_handle($this->master, $done['handle']);
                } else {
                    // request failed.  add error handling.
                }
            }
        } while ($running);
    }
}