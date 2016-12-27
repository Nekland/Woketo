Woketo
======

A PHP WebSocket library. With following features:

* Autobahn test suite passed (WebSocket test suite reference)
* Binary/text messages supported
* Built on top of reactphp (async socket communication)
* *Not dependent of any other big framework/library which mean you can use with guzzle (any version) or Symfony (any version)*

[![Build Status](https://travis-ci.org/Nekland/Woketo.svg?branch=master)](https://travis-ci.org/Nekland/Woketo) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Nekland/Woketo/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Nekland/Woketo/?branch=master)

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
// YourMessageHandler.php

namespace Your\Namespace;

use Nekland\Woketo\Server\Connection;
use Nekland\Woketo\Message\TextMessageHandler

class YourMessageHandler extends TextMessageHandler
{
    public function onConnection(Connection $connection)
    {
        // Doing something when the client is connected ?
        // This method is totally optional.
    }
    
    public function onMessage(string $data, Connection $connection)
    {
        // Sending back the received data
        $connection->write($data);
    }
}
```

How to test
-----------

### Unit tests suite

```bash
git clone git@github.com:Nekland/Woketo
cd Woketo
composer install
./bin/phpunit
```


### Functionnal tests suite

```bash
php tests.php
wstest -m fuzzingclient
```

> wstest is the Autobahn test tool. You can install it with `sudo pip install autobahntestsuite`.
>
> You can read more about on [their documentation](http://autobahn.ws/testsuite/installation.html#installation).

How to something else ?
-----------------------

* How to learn more about Woketo usage ? [RTFM](docs/index.md) !
* How to get information about how it work internally ? Read the [docs/dev.md](docs/dev.md) page of doc.
* How to contribute ? Read the [CONTRIBUTING.md](CONTRIBUTING.md) page of doc.
* How to get support ? [Use Gitter](http://gitter.im/Nekland/Woketo), _the issue tracker is **not** a forum_.

What's next ?
-------------

You can see what's plan for next versions in the [github milestones](https://github.com/Nekland/Woketo/milestones).
