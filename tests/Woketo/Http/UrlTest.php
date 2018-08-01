<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Http;


use Nekland\Woketo\Http\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testItUnderstandUrlAndIsAbleToReturnIt()
    {
        $url = new Url('wss://localhost:9000/hello');

        $this->assertSame($url->getHost(), 'localhost');
        $this->assertSame($url->getPort(), 9000);
        $this->assertSame($url->getUri(), '/hello');
        $this->assertSame($url->isSecured(), true);

        $this->assertSame((string) $url, 'wss://localhost:9000/hello');

        $url->setHost('127.0.0.1');
        $url->setSecured(false);

        $this->assertSame((string) $url, 'ws://127.0.0.1:9000/hello');
    }

    public function testItReplaceEmptyUriBySlash()
    {
        $url = new Url('ws://10.20.0.1:800');

        $this->assertSame($url->getUri(), '/');
        $this->assertSame((string) $url, 'ws://10.20.0.1:800/');
    }

    public function testItAcceptEmptyUri()
    {
        $url = new Url('ws://10.20.0.1:800/');

        $this->assertSame($url->getUri(), '/');
    }
}
