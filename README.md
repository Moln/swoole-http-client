# SwooleHttpClient
封装 Swoole HTTP Client, Response 响应使用psr7 对象, 支持 psr7 request .


## Install / 安装

请使用 Composer 安装

```json
{
    "require": {
        "moln/swoole-http-client": "^1.0.0"
    }
}
```

## Usage / 使用


### `Moln\SwooleHttpClient\Client`

```php
use Moln\SwooleHttpClient\Client;
$cli = new Client('192.168.11.5');
$cli->get('/test/1.php', function (\Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
});
```

### `Moln\SwooleHttpClient\ClientStatic` 静态调用


```php
use Moln\SwooleHttpClient\ClientStatic;
use Psr\Http\Message\ResponseInterface;

$cli = ClientStatic::get('http://192.168.11.5/test/1.php', function (ResponseInterface $response) {
 var_dump((string)$response->getBody());
})->on('connect', function () {
  var_dump('connect');
});

$cli = ClientStatic::post('http://192.168.11.5/test/1.php', ['key' => 'val'], function (ResponseInterface $response) {
 var_dump((string)$response->getBody());
});

```

### `Moln\SwooleHttpClient\ClientChain` http 客户端链（复用 client 连接对象）

```php
$client = new \Moln\SwooleHttpClient\ClientChain('192.168.11.5');
$client->on('connect', function ($client) {
    var_dump(spl_object_hash($client));
});

//New 一个新的 Client
$client->get('/test/1.php?s=2&id=3', function ($response) use ($client) {
    //复用 client 对象
    var_dump(spl_object_hash($client->get('/test/1.php?s=1&id=5')));;
    var_dump(spl_object_hash($client->get('/test/1.php?s=1&id=6')));;
});

//没有空闲Client 对象，再 new 一个 Client
$client->get('/test/1.php?s=1&id=4',  function ($response) use ($client) {
});
```

## Features / 特点

### 支持多个事件绑定,官方仅支持1个

```php
use Moln\SwooleHttpClient\Client;
$cli = new Client('192.168.11.5');
$cli->setHeaders(['Connection' => 'close']);
$cli->on('connect', function () use ($cli) {
    var_dump('connect event1', $cli->isConnected());
});
$cli->on('connect', function () use ($cli) {
    var_dump('connect event2', $cli->isConnected());
});
$cli->on('error', function () use ($cli) {
    var_dump('connect event2', $cli->isConnected());
});
```

### 响应的 Response 为 PSR7

```php
use Moln\SwooleHttpClient\Client;
$cli = new Client('192.168.11.5');
$cli->get('/test/1.php', function (\Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
});
$cli->post('/test/1.php', ['a' => 1], function (\Psr\Http\Message\ResponseInterface $response) {
    var_dump((string)$response->getBody());
});
```

### 支持PSR7 Request

```php
use Moln\SwooleHttpClient\Client;
$cli = new Client('192.168.11.5');
$cli->send(new \GuzzleHttp\Psr7\Request('POST', '/test/1.php?s=1&id=2', ['X-xxx' => '123'], 'x=1'), 
    function ($response) {
        var_dump((string)$response->getBody());
    }
);
```

### 支持 Timeout 超时（连接超时，连接响应超时）

使用 `Swoole\Timer::after` 实现连接超时

```php
use Moln\SwooleHttpClient\Client;
$cli = new Client('192.168.11.5');
$cli->setTimeout(4);
$cli->setHeaders(['Connection' => 'close']);
$cli->on('connect', function () use ($cli) {
    var_dump('connect', $cli->isConnected());
});
$cli->on('error', function ($cli, $msg) {
    //$cli->isConnected() 判断是连接超时，还是连接上了响应超时
    var_dump('connect error', $msg, $cli->isConnected()); // $msg= 'timeout' ; $msg = 'error'; 
});
```

