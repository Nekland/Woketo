Getting started with Woketo
===========================

Installation
------------

Checkout the [README to learn about installation](../README.md#how-to-install).

Usage
-----

### How it works

Basically, you just need to create a class that will handle websocket messages and process them. You give this an instance
of this class to Woketo that call `onMessage` method. This class must implements the
[`MessageHandlerInterface`](../src/Message/MessageHandlerInterface.php).

Here is how your class may look:

```php
<?php
class MyMessageHandler implements MessageHandlerInterface
{
    public function onConnection(Connection $connection)
    {
        // This method is called when a new client is connected to your server
    }
    
    public function onMessage(string $data, Connection $connection)
    {
        // This method is called when a text message is send
    }
    
    public function onBinary(string $data, Connection $connection)
    {
        // This method is called when a binary message is send
    }
    
    public function onError(WebsocketException $e, Connection $connection)
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
$server->setMessageHandler(new YourMessageHandler());
$server->start();
```

The `Websocket` instantiation take the following parameters:
- `1337`: the port you want to bind your server on (notice that for low ports you need root rights)
- `"127.0.0.1"`: the host you bind on, this is the default value and what you need most part of the time
- `[]`: an (optional) array of configuration option documented in the [configuration reference](#configuration-reference)

The `Connection` object have the following methods you can use:
- `write($message, $opCode = Frame::OP_TEXT)`, you may change to `Frame::OP_BINARY` if you want to send binary data
- `getIp()`, that returns the current IP
- `getLogger()`, that returns the logger of woketo

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

The configuration is split in some parts:
- The `frame` key configure the way Frames are managed
- The `message` key configure the way Frames are stacked
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
    'prod' => true,
];
```

### Logs

Woketo provides a custom logger but you may want to log with yours. It's easy as Woketo uses psr-3 log system.

```php
<?php

$server = new WebSocketServer(1337);
$server->setLogger($myLogger);
$server->run();
```

> Notice that this defined logger will be accessible from `Connection::getLogger()`.

### Message Handler

A message handler is an object you can re-use that handle a specific type of message and throw a specific related
exception or answer a close message if needed. This class must implement `Nekland\Woketo\Rfc6455\MessageHandler\Rfc6455MessageHandlerInterface`.

Please consider that Woketo only catches `WebsocketException` which mean that if you need to throw an exception, that
needs to be catch, it must have this type.

Contributing to the development of Woketo
-----------------------------------------

There is some documentation about [how to contribute in CONTRIBUTE.md](../CONTRIBUTE.md) and some [help for developers in dev.md](dev.md).
