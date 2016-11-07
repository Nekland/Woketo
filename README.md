Woketo
======

**A websocket library designed to be released.**

> This means there is no version  that is going to be release until [v1 milestone](https://github.com/Nekland/Woketo/milestone/1) is complete.

[![Build Status](https://travis-ci.org/Nekland/Woketo.svg?branch=master)](https://travis-ci.org/Nekland/Woketo) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master)

### TODO

- [x] Handcheck
- [x] Reading messages/frames
- [x] Writing messages
- [ ] Add websocket client API
- [ ] Cleaning code and design complete user API
- [ ] Pass wstest (full autobahn test suite !)
- [ ] Writing docs !

Requirements
------------

* PHP 7+

How to install
--------------

*Woketo is not on composer for now because it's not released. If you really want to use it you can add it as git dependency.*

```bash
# The installation is pretty much easier with composer. But you still can use it as git submodule !
composer require "nekland/woketo"
```

How to use it
-------------

The file `tests.php` is a basic echo server. That's all you need to make a websocket server with Woketo:


```php
<?php

use Your\Namespace\YourMessageHandler;

$server = new Websocket(1337);
$server->setMessageHandler(new YourMessageHandler());
$server->start(); // And that's all <3
```

```php
<?php
// MessageHandler.php

namespace Your\Namespace;

use Nekland\Woketo\Server\Connection;

class YourMessageHandler implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(Connection $connection)
    {
        // Doing something when the client is connected ?
    }
    
    public function onMessage(string $data, Connection $connection)
    {
        // Sending back the received data
        $connection->write($data);
    }
    
    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, Connection $connection)
    {
        // Many exceptions are catched by default to prevent your server to crash
        // (which mean service interruption)
        echo '(' . get_class($e) . ') ' . $e->getMessage() . "\n";
    }
    
    // You probably do not want to implement this class as its about binary data transfer
    // which only fit very specific cases.
    public function onBinary(string $data, Connection $connection){}
}
```

***Please consider that the API is not stable at this state.***

How to test
-----------

### Unit tests suite

```
git clone woketo
./bin/phpunit

```


### Functionnal tests suite

```
php tests.php
wstest -m fuzzingclient
```

> wstest is the autobahn test tool. You can install it with `sudo pip install autobahntestsuite`. You can read more on [their documentation](http://autobahn.ws/testsuite/installation.html#installation).
