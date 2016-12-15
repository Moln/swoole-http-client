<?php

namespace Moln\SwooleHttpClient;
use Swoole\Http\Client as SwooleHttpClient;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;


/**
 * Class Psr7Response
 *
 * @package QueueApplication\Http
 */
class Psr7Response
{

    public static function fromSwoole(SwooleHttpClient $client)
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($client->body);
        return new Response($body, $client->statusCode, $client->headers);
    }
}
