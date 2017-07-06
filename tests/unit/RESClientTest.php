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
use res\libres\RESClient;
use res\libres\RESMedia;

const FIXTURES_AUDIENCE = __DIR__ . '/../fixtures/audiences.ttl';
const FIXTURES_SEARCH_DENCH = __DIR__ . '/../fixtures/search_dench.ttl';

final class RESClientTest extends TestCase
{
    function testAudiences()
    {
        $lod = new LOD();
        $lod->loadRdf(file_get_contents(FIXTURES_AUDIENCE), 'text/turtle');

        $client = new RESClient(NULL, $lod);

        // the audiences() method uses resolve(), so will only do a fetch
        // when the audiences URI isn't in the context (it is, as we load the
        // RDF for it); no need to stub fetch()
        $audiences = $client->audiences();

        $this->assertEquals(4, count($audiences));
    }

    function testSearch()
    {
        $lod = new LOD();
        $lod->loadRdf(file_get_contents(FIXTURES_SEARCH_DENCH), 'text/turtle');

        // search uses resolve(), so will only do HTTP GET if the search URI
        // isn't in the context; it is, because we manually load its RDF
        $client = new RESClient(NULL, $lod);
        $results = $client->search('dench', RESMedia::IMAGE, 5);

        $acropolisUri = 'http://acropolis.org.uk/?limit=5&media=image&q=dench';
        $this->assertEquals($acropolisUri, $results['acropolis_uri']);
        $this->assertEquals(4, count($results['items']));
        $this->assertTrue($results['hasNext']);
        $this->assertEquals(0, $results['offset']);
        $this->assertEquals(5, $results['limit']);
        $this->assertEquals('dench', $results['query']);
    }

    function testProxy()
    {
    }
}
