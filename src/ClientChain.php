<?php

namespace Moln\SwooleHttpClient;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;

/**
 * Class HttpClientChain
 *
 * @package QueueApplication\Client
 */
class ClientChain implements EventEmitterInterface
{
    use EventEmitterTrait;

    protected $host;
    protected $port;
    protected $timeout;

    /** @var Client[]  */
    protected $clients;
    protected $headers = [
        'Connection' => 'keep-alive'
    ];

    protected $cookies = [];

    public function __construct($host, $port = 80, $timeout = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        $this->clients = new \SplQueue();
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;

        return $this;
    }

    public function get($uri, callable $callback = null)
    {
        $client = $this->pop();
        $client->get($uri, function ($response) use ($client, $callback) {
            $this->push($client);
            $callback && $callback($response);
        });

        return $client;
    }

    public function post($uri, $params, callable $callback = null)
    {
        $client = $this->pop();
        $client->post($uri, $params, function ($response) use ($client, $callback) {
            $this->push($client);
            $callback && $callback($response);
        });.

        return $client;
    }

    protected function newClient()
    {
        $client = new Client($this->host, $this->port, $this->timeout);
        $client->setHeaders($this->headers);
        if (count($this->cookies)) $client->setCookies($this->cookies);

        /** @var \Closure[] $listeners */
        foreach ($this->listeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                $listener->bindTo($client);
                $client->on($event, $listener);
            }
        }

        return $client;
    }

    /**
     * @return Client
     */
    protected function pop()
    {
        if ($this->clients->count() == 0) {
            $this->push($this->newClient());
        }

        return $this->clients->pop();
    }

    protected function push($client)
    {
        $this->clients->push($client);
    }
}
