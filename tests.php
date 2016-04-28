<?php

use Nekland\Woketo\Server\Websocket;

require 'vendor/autoload.php';

$foo = new Websocket(8088);

$foo->start();
