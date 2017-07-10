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

use \res\liblod\LOD;
use res\libres\RESTopicConverter;

const FIXTURES_CONVERTER_TURTLE = __DIR__ . '/../fixtures/topicconverter.ttl';
const FIXTURES_CONVERTER_EXPECTED_JSON = __DIR__ . '/../fixtures/topicconverter.out.json';
const FIXTURES_CONVERTER_EXPECTED_TEXT_JSON = __DIR__ . '/../fixtures/topicconvertertext.out.json';

final class RESTopicConverterTest extends TestCase
{
    /* inputs to CONVERTER->convert():
     * proxyUri = 'http://foo.bar/person1proxy'
     * media = 'image'
     * slotItemUris = ['http://foo.bar/image1proxy', 'http://foo.bar/image2proxy']
     */
    public function testConvert()
    {
        $lod = new LOD();
        $lod->loadRdf(file_get_contents(FIXTURES_CONVERTER_TURTLE), 'text/turtle');

        $converter = new RESTopicConverter($lod);

        $expected = json_decode(file_get_contents(FIXTURES_CONVERTER_EXPECTED_JSON), TRUE);

        $slotItemUris = array(
            'http://foo.bar/image1proxy',
            'http://foo.bar/image2proxy'
        );

        $actual = $converter->convert(
            'http://foo.bar/person1proxy',
            'image',
            $slotItemUris
        );

        $this->assertEquals($expected, $actual);
    }

    public function testConvertText()
    {
        $lod = new LOD();
        $lod->loadRdf(file_get_contents(FIXTURES_CONVERTER_TURTLE), 'text/turtle');

        $converter = new RESTopicConverter($lod);

        $expected = json_decode(file_get_contents(FIXTURES_CONVERTER_EXPECTED_TEXT_JSON), TRUE);

        $slotItemUris = array(
            'http://foo.bar/image1proxy',
            'http://foo.bar/image2proxy'
        );

        $actual = $converter->convert(
            'http://foo.bar/person1proxy',
            'text',
            $slotItemUris
        );

        $this->assertEquals($expected, $actual);
    }
}
