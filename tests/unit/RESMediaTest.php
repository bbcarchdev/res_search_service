<?php
/*
 * Copyright 2017 BBC
 *
 * Author: Elliot Smith <elliot.smith@bbc.co.uk>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use PHPUnit\Framework\TestCase;

use \bbcarchdev\liblod\LOD;
use res\libres\RESMedia;

const FIXTURES_MEDIA_TURTLE = __DIR__ . '/../fixtures/mediaexamples.ttl';

final class RESMediaTest extends TestCase
{
    private $lod;

    public function setUp()
    {
        $this->lod = new LOD();
        $this->lod->loadRdf(file_get_contents(FIXTURES_MEDIA_TURTLE), 'text/turtle');
    }

    private function getMediaType($uri)
    {
        $instance = $this->lod[$uri];
        return RESMedia::getMediaType($instance);
    }

    public function testGetMediaType()
    {
        $this->assertEquals('image', $this->getMediaType('http://foo.bar/i1'));
        $this->assertEquals('image', $this->getMediaType('http://foo.bar/i2'));
        $this->assertEquals('image', $this->getMediaType('http://foo.bar/i3'));
        $this->assertEquals('image', $this->getMediaType('http://foo.bar/i4'));

        $this->assertEquals('video', $this->getMediaType('http://foo.bar/v1'));
        $this->assertEquals('video', $this->getMediaType('http://foo.bar/v2'));
        $this->assertEquals('video', $this->getMediaType('http://foo.bar/v3'));
        $this->assertEquals('video', $this->getMediaType('http://foo.bar/v4'));

        $this->assertEquals('audio', $this->getMediaType('http://foo.bar/a1'));
        $this->assertEquals('audio', $this->getMediaType('http://foo.bar/a2'));
        $this->assertEquals('audio', $this->getMediaType('http://foo.bar/a3'));

        $this->assertEquals('text', $this->getMediaType('http://foo.bar/t1'));
    }

    public function testGetMediaTypeNoInstance()
    {
        $this->assertEquals(NULL, RESMedia::getMediaType(NULL));
    }

    public function testGetMediaTypeNoRecognisedType()
    {
        $this->assertEquals(NULL, $this->getMediaType('http://foo.bar/x1'));
    }
}
