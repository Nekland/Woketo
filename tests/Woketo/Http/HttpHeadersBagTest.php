<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Http;

use Nekland\Woketo\Http\HttpHeadersBag;

class HttpHeadersBagTest extends \PHPUnit_Framework_TestCase
{
    public function testItKeepAndRetrieveHeaders()
    {
        $bag = new HttpHeadersBag();
        $bag->set('Content-Type', 'text/html');
        $this->assertSame($bag->get('Content-Type'), 'text/html');
    }

    public function testItIsNonCaseSensitive()
    {
        $bag = new HttpHeadersBag();
        $bag->set('Custom-Header', 'foobar');
        $this->assertSame($bag->get('Custom-Header'), 'foobar');
    }

    public function testItSupportsManytimesSameHeader()
    {
        $bag = new HttpHeadersBag();
        $bag->add('Sec-WebSocket-Extensions', 'yolo');
        $bag->add('Sec-WebSocket-Extensions', 'loyo');
        
        $this->assertSame($bag->get('Sec-WebSocket-Extensions'), ['yolo', 'loyo']);

        $bag->set('Sec-WebSocket-Extensions', 'oups');
        $this->assertSame($bag->get('Sec-WebSocket-Extensions'), 'oups');
        
        $bag->add('Sec-WebSocket-Extensions', 'yolo');
        $this->assertSame($bag->get('Sec-WebSocket-Extensions'), ['oups', 'yolo']);
    }
    
    public function testItCanBeInitializedWithArrayOfHeaders()
    {
        $bag = new HttpHeadersBag([
            'Content-Type' => 'text/javascript',
            'Sec-WebSocket-Extensions' => 'test'
        ]);
        
        $this->assertSame($bag->get('Content-Type'), 'text/javascript');
    }
    
    public function testItReturnDefaultValueWhenSpecifiedIfHeaderDoesNotExists()
    {
        $bag = new HttpHeadersBag();
        
        $this->assertSame($bag->get('Content-Not-Specified'), null);
        $this->assertSame($bag->get('Content-Specified', 'Hello'), 'Hello');
    }
}
