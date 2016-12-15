<?php

namespace Moln\SwooleHttpClient;
use Psr\Http\Message\RequestInterface;

/**
 * Class Client
 *
 * @package QueueApplication\Http
 */
class ClientStatic
{
    protected static $defaultHeaders = [
        'Connection' => 'close',
    ];

    /**
     * @return array
     */
    public static function getDefaultHeaders()
    {
        return self::$defaultHeaders;
    }

    /**
     * @param array $defaultHeaders
     */
    public static function setDefaultHeaders(array $defaultHeaders)
    {
        self::$defaultHeaders = $defaultHeaders;
    }

    public static function get($url, callable $callback, $headers = [], $timeout = 0)
    {
        $urlInfo = parse_url($url) + ['port' => 80, 'path' => '/'];
        $client = new Client($urlInfo['host'], $urlInfo['port'], $timeout);
        $client->setHeaders($headers + self::$defaultHeaders);
        $client->get($urlInfo['path'] . (empty($urlInfo['query']) ? '' : ('?' . $urlInfo['query'])), $callback);

        return $client;
    }

    public static function post($url, $bodyOrParams, callable $callback, $headers = [], $timeout = 0)
    {
        $urlInfo = parse_url($url) + ['port' => 80, 'path' => '/'];
        $client = new Client($urlInfo['host'], $urlInfo['port'], $timeout);
        $client->setHeaders($headers + self::$defaultHeaders);
        $client->post(
            $urlInfo['path'] . (empty($urlInfo['query']) ? '' : ('?' . $urlInfo['query'])),
            $bodyOrParams,
            $callback
        );

        return $client;
    }

    public static function send(RequestInterface $psrRequest, callable $callback, $timeout = 0)
    {
        $uri = $psrRequest->getUri();
        $client = new Client($uri->getHost(), $uri->getPort(), $timeout);
        $client->send($psrRequest, $callback);

        return $client;
    }
}
