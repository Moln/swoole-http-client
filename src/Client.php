<?php

namespace Moln\SwooleHttpClient;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Psr\Http\Message\RequestInterface;
use Swoole\Timer;


/**
 * Class Client
 *
 * @package QueueApplication\Http
 */
class Client implements EventEmitterInterface
{
    use EventEmitterTrait;
    protected $swooleClient;

    protected $callback;
    protected $headers;
    protected $timeout;
    protected $promise;

    /**
     * Client constructor.
     *
     * @param $host
     * @param $port
     * @param int $timeout sec
     */
    public function __construct($host, $port = 80, $timeout = 0)
    {
        $this->swooleClient = new \Swoole\Http\Client($host, $port);
        $this->timeout = $timeout;

        foreach (['error', 'close', 'connect'] as $event) {
            $this->swooleClient->on($event, function () use ($event) {
                if ($event == 'error') {
                    $this->emit($event, [$this, 'error']);
                } else {
                    $this->emit($event, [$this]);
                }
            });
        }
    }

    public function send(RequestInterface $request, callable $callback = null)
    {
        $this->swooleClient->setMethod($request->getMethod());
        $headers = [];
        foreach ($request->getHeaders() as $key => $vals) {
            $headers[$key] = implode(',', $vals);
        }

        if ($request->getBody()->getSize()) {
            $this->swooleClient->setData((string)$request->getBody());
        }
        $this->swooleClient->setHeaders($headers);
        $this->swooleClient->execute($request->getUri(), $this->commonCallback($callback));
    }

    public function isConnected()
    {
        return $this->swooleClient->isConnected();
    }

    public function get($uri, callable $callback = null)
    {
        $this->swooleClient->get($uri, $this->commonCallback($callback));

        return $this;
    }

    public function post($uri, $bodyOrParams, callable $callback = null)
    {
        $this->swooleClient->post($uri, $bodyOrParams, $this->commonCallback($callback));

        return $this;
    }

    private function commonCallback($callback)
    {
        $timerId = $this->startTimerTimeout();

        return function ($response) use ($callback, $timerId) {
            $timerId && Timer::clear($timerId);
            $callback && $callback(Psr7Response::fromSwoole($response));
        };
    }

    private function startTimerTimeout()
    {
        $timerId = null;
        if ($this->timeout) {
            $timerId = Timer::after($this->timeout * 1000, function () {
                $this->emit('error', [$this, 'timeout']);
                $this->swooleClient->close();
            });
        }

        return $timerId;
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setHeaders(array $headers)
    {
        $this->swooleClient->setHeaders($headers);
        return $this;
    }

    public function setCookies(array $cookies)
    {
        $this->swooleClient->setCookies($cookies);
        return $this;
    }
}