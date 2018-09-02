# Woketo
======

A PHP WebSocket library. With the following features:

* **Server**
* **Client**
* WSS Support (WebSocket over **SSL**)
* Autobahn test suite passed (WebSocket test suite reference)
* Binary/text messages supported
* Built on top of reactphp (async socket communication)
* *Does not depend on any other big framework/library, which means that you can use it with guzzle (any version) or Symfony (any version)*
* Woketo follows semver

[![Build Status](https://travis-ci.org/Nekland/Woketo.svg?branch=master)](https://travis-ci.org/Nekland/Woketo) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master)

Requirements
------------

* PHP 7+

How to install Woketo:
--------------

```bash
# The installation is easier with composer but you can still use it as a git submodule!
composer require "nekland/woketo"
```

And that's it! :scream_cat: 

How to use Woketo:
-------------

The file `tests.php` is a basic echo server. That's all you need to make a websocket server with Woketo:


```php
<?php

use Your\Namespace\YourMessageHandler;
use Nekland\Woketo\Server\WebSocketServer;

$server = new WebSocketServer(1337);
$server->setMessageHandler(new YourMessageHandler(), '/path'); // accessible on ws://127.0.0.1:1337/path
$server->start(); // And that's all <3
```

```php
<?php
// YourMessageHandler.php

namespace Your\Namespace;

use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Message\TextMessageHandler;

class YourMessageHandler extends TextMessageHandler
{
    public function onConnection(AbstractConnection $connection)
    {
        // Doing something when the client is connected?
        // This method is totally optional.
    }
    
    public function onMessage(string $data, AbstractConnection $connection)
    {
        // Sending back the received data
        $connection->write($data);
    }
}
```

How to test Woketo:
-----------

### Unit tests suite

```bash
git clone git@github.com:Nekland/Woketo
cd Woketo
composer install
./bin/phpunit
```


### Functional tests suite

#### Server test suite

```bash
php tests/echo-server.php
wstest -m fuzzingclient
```

#### Client test suite

```bash
wstest -m fuzzingserver
php tests/client_autobahn.php
```

> wstest is the Autobahn test tool. You can install it with `sudo pip install autobahntestsuite`.
>
> You can read more about the installation of Autobahn on
> [their documentation](http://autobahn.ws/testsuite/installation.html#installation).

How to something else?
-----------------------

* Learn more about Woketo usage? [RTFM](docs/index.md)!
* Get more information about how it works internally? Read the [docs/dev.md](docs/dev.md) page of doc.
* Contribute! You can read the [CONTRIBUTING.md](CONTRIBUTING.md) page of doc.
* Do you need assistance? [Use Gitter](http://gitter.im/Nekland/Woketo), _the issue tracker is **not** a forum_.

What's next?
-------------

You can see what's planned for next versions in the [github milestones](https://github.com/Nekland/Woketo/milestones).

What Woketo does _not_ do?
---------------------------

Currently there is no support of the following:

- WebSocket extensions, currently not supported but probably will be in the future
- [WAMP](http://wamp-proto.org/) implementation might never be done by Woketo itself provided it's a layer on top of
  WebSockets. This includes JSON-RPC and other layers up to WebSockets.
- Woketo does not have an HTTP server layer and is not compatible with [PHP PM](https://github.com/php-pm/php-pm). This is planned in the future. (probably the v3).

Thank you!
------

Thank you to the code contributors (Hello [folliked](https://github.com/folliked) =)). As well as the code reviewers (Hi [valanz](https://github.com/valanz)).

<3
