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

use Nekland\Woketo\Http\HttpHeadersBag;
use PHPUnit\Framework\TestCase;

class HttpHeadersBagTest extends TestCase
{
    public function testItKeepAndRetrieveHeaders()
    {
        $bag = new HttpHeadersBag();
        $bag->set('Content-Type', 'text/html');
        $this->assertSame('text/html', $bag->get('Content-Type'));
    }

    public function testItIsNonCaseSensitive()
    {
        $bag = new HttpHeadersBag();
        $bag->set('Custom-Header', 'foobar');
        $this->assertSame('foobar', $bag->get('Custom-Header'));
    }

    public function testItSupportsManytimesSameHeader()
    {
        $bag = new HttpHeadersBag();
        $bag->add('Sec-WebSocket-Extensions', 'yolo');
        $bag->add('Sec-WebSocket-Extensions', 'loyo');
        
        $this->assertSame(['yolo', 'loyo'], $bag->get('Sec-WebSocket-Extensions'));

        $bag->set('Sec-WebSocket-Extensions', 'oups');
        $this->assertSame('oups', $bag->get('Sec-WebSocket-Extensions'));
        
        $bag->add('Sec-WebSocket-Extensions', 'yolo');
        $this->assertSame(['oups', 'yolo'], $bag->get('Sec-WebSocket-Extensions'));
    }
    
    public function testItCanBeInitializedWithArrayOfHeaders()
    {
        $bag = new HttpHeadersBag([
            'Content-Type' => 'text/javascript',
            'Sec-WebSocket-Extensions' => 'test'
        ]);
        
        $this->assertSame('text/javascript', $bag->get('Content-Type'));
    }
    
    public function testItReturnDefaultValueWhenSpecifiedIfHeaderDoesNotExists()
    {
        $bag = new HttpHeadersBag();
        
        $this->assertNull($bag->get('Content-Not-Specified'));
        $this->assertSame('Hello', $bag->get('Content-Specified', 'Hello'));
    }
}
