Getting started with Woketo
===========================

- [Installation](#installation)
- [Usage](#usage)
  * [Serveur](#server)
  * [Client](#client)
- [Configuration Reference](#configuration-reference)
- [Cookbooks](#cookbooks)
  * [Logs](#logs)
  * [Message Handlers](#message-handlers)
  * [WebSocket Secured](#websocket-secured-aka-wss)
- [Optimization](#optimization)
- [Contributing](#contributing-to-the-development-of-woketo)

Installation
------------

Checkout the [README to learn about installation](../README.md#how-to-install).

Usage
-----

### Server

#### How it works

Basically, you just need to create a class that will handle websocket messages and process them. You give this an instance
of this class to Woketo that call `onMessage` method. This class must implements the
[`MessageHandlerInterface`](../src/Message/MessageHandlerInterface.php).

Here is how your class may look:

```php
<?php
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\WebsocketException;

class MyMessageHandler implements MessageHandlerInterface
{
    public function onConnection(AbstractConnection $connection)
    {
        // This method is called when a new client is connected to your server
    }
    
    public function onMessage(string $data, AbstractConnection $connection)
    {
        // This method is called when a text message is sent
    }
    
    public function onBinary(string $data, AbstractConnection $connection)
    {
        // This method is called when a binary message is sent
    }
    
    public function onError(WebsocketException $e, AbstractConnection $connection)
    {
        // This method is called when an error occurs
    }
}
```

The behavior of any of this methods is up to you.

To use your class it's pretty easy:

```php
<?php

$server = new WebSocketServer(1337, '127.0.0.1', []);
$server->setMessageHandler(new YourMessageHandler(), '/path');
$server->start();
```

The `WebSocketServer` instantiation takes the following parameters:
- `1337`: the port you want to bind your server on (notice that for low ports you need root rights)
- `"127.0.0.1"`: the host you bind on, this is the default value and what you need most part of the time
- `[]`: an (optional) array of configuration option documented in the [configuration reference](#server-configuration)

The `setMessageHandler` method takes 2 parameters:
- Your message handler
- The path on the websocket server it will be trigger `ws://127.0.0.1:1337/path`

The `Connection` object has the following methods you can use:
- `write($message, $opCode = Frame::OP_TEXT)`, you may change to `Frame::OP_BINARY` if you want to send binary data
- `getIp()`, that returns the current IP
- `getLogger()`, that returns the logger of woketo

### Client

The client usage is as simple as is the server usage. First you create your message handler:
```php
<?php
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\WebsocketException;

class MyMessageHandler implements MessageHandlerInterface
{
    public function onConnection(AbstractConnection $connection)
    {
        // This method is called when a new client is connected to your server
    }
    
    public function onMessage(string $data, AbstractConnection $connection)
    {
        // This method is called when a text message is sent
    }
    
    public function onBinary(string $data, AbstractConnection $connection)
    {
        // This method is called when a binary message is sent
    }
    
    public function onError(WebsocketException $e, AbstractConnection $connection)
    {
        // This method is called when an error occurs
    }
}
```

You can then run your client:

```php
$client = new WebSocketClient('ws://127.0.0.1:9000/foobar', []);
$client->start(new MyMessageHandler);
```


The `WebSocketClient` instantiation takes the following parameters:
- `"ws://127.0.0.1:9000/foobar""`: a WebSocket URL: the protocol (ws or wss), the host (127.0.0.1), the port (9000), the URI (foobar)
- `[]`: an (optional) array of configuration option documented in the [configuration reference](#client-configuration)


### Use it your way

Here are some helpers you can use depending on your use-case:

- You want to create a server that only supports text messages:
  extends the [TextMessageHandler](../src/Message/TextMessageHandler.php) class
- You want to create a server that only supports binary messages:
  extends the [BinaryMessageHandler](../src/Message/BinaryMessageHandler.php) class
- You want to supports both message types without care about the connection or error events:
  extends the [SimpleMessageHandler](../src/Message/SimpleMessageHandler.php) class

Of course you can redefine the method you want from `MessageHandlerInterface` on any of your message handler as they
implement it.

Configuration Reference
-----------------------

### Server configuration

The configuration is split in some parts:
- The `frame` key configures the way Frames are managed
- The `message` key configures the way Frames are stacked
- The `messageHandlers` key contains your custom message handler, checkout the [message handler doc](#message-handler)
- The `prod` key defines if your running environment is prod or not (similar to `debug` parameter in some environment)

```php
<?php

$defaultConfiguration = [
    'frame'           => [
        'maxPayloadSize' => 524288,   // 0.5 MiB per Frame
    ],
    'message'         => [
        'maxMessagesBuffering' => 100 // 100 * 0.5 MiB max in memory
    ],
    'messageHandlers' => [],          // Empty by default, you can add some
    'prod' => true,                   // When set to false, it allows you to launch woketo with xdebug
    'ssl' => true,                    // to use wss
    'certFile' => '',                 // pem file, see ssl doc section for more details
    'sslContextOptions' => [],        // PHP SSL configuration see http://php.net/manual/fr/context.ssl.php
];
```

### Client configuration

The configuration is split in some parts:
- The `frame` key configures the way Frames are managed
- The `message` key configures the way Frames are stacked
- The `prod` key defines if your running environment is prod or not (similar to `debug` parameter in some environment)

```php
<?php

$defaultConfiguration = [
    'frame'           => [
        'maxPayloadSize' => 524288,   // 0.5 MiB per Frame
    ],
    'message'         => [
        'maxMessagesBuffering' => 100 // 100 * 0.5 MiB max in memory
    ],
    'prod' => true,                   // When set to false, it allows you to launch woketo with xdebug
    'dns' => '',                      // Server address for DNS resolution (google by default)
    'ssl' => [],                      // PHP SSL configuration see http://php.net/manual/fr/context.ssl.php
];
```

Cookbooks
---------

### Logs

Woketo provides a custom logger but you may want to log with yours. It's easy as Woketo uses psr-3 log system.

```php
<?php

$server = new WebSocketServer(1337);
$server->setLogger($myLogger);
$server->run();
```

> Notice that the logger you give will be accessible from `Connection::getLogger()`.

### Message Handlers

A message handler is an object you can re-use that handle a specific type of message and throw a specific related
exception or answer a close message if needed. This class must implement `Nekland\Woketo\Rfc6455\MessageHandler\Rfc6455MessageHandlerInterface`.

Please consider that Woketo only catches `WebsocketException` which mean that if you need to throw an exception, that
needs to be catch, it must have this type.

### WebSocket Secured (alias WSS)

In new apps you often use https. So you should use wss with WebSockets to secure data exchange. Woketo
supports wss out of the box, you just need to add the related options (`ssl` and `certFile`).

You should instanciate woketo like this:

```php
$server = new \Nekland\Woketo\Server\WebSocketServer(9001, '127.0.0.1', [
    'ssl' => true,
    'certFile' => 'path/to/certificate/cert.pem',
    'sslContextOptions' => [
        'verify_peer' => false,
        'allow_self_signed' => true
    ]
]);
```

> ❓ Why is there only one cert file required while I have 2 files (cert and private key) ?

PHP uses a PEM formatted certificate that contains the certificate *and* the private key.

Here is a way to generate your PEM formatted certificate for a local usage:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout acme.key -out acme.crt
cat acme.key > acme.pem
cat acme.crt >> acme.pem
```

Optimization
------------

### Libevent

Woketo is based on ReactPHP and as ReactPHP is able to run a loop by itself, so woketo is.
But something good to note is that you can install the **PHP extension libevent** to get better performance
for your event loop. You use Woketo exactly the same with libevent, it will just be faster and safer.

[Learn more about libevent](http://www.wangafu.net/~nickm/libevent-book/)

Contributing to the development of Woketo
-----------------------------------------

There is some documentation about [how to contribute in CONTRIBUTE.md](../CONTRIBUTE.md) and some [help for developers in dev.md](dev.md).
