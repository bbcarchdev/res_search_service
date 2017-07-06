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
use \res\liblod\LODInstance;
use \res\liblod\Parser;
use res\libres\RESClient;
use res\libres\RESMedia;
use res\libres\RESTopicConverter;

const FIXTURES_AUDIENCE = __DIR__ . '/../fixtures/audiences.ttl';
const FIXTURES_SEARCH_DENCH = __DIR__ . '/../fixtures/search_dench.ttl';
const FIXTURES_PROXY_DENCH = __DIR__ . '/../fixtures/proxy_dench.ttl';

final class RESClientTest extends TestCase
{
    // create a mock LODInstance containing the RDF/Turtle in the string $rdf
    private function createStubLODInstance($lod, $uri, $rdf)
    {
        $parser = new Parser();

        $stubinstance = new LODInstance($lod, $uri);

        $triples = $parser->parse($rdf, 'text/turtle');

        foreach($triples as $triple)
        {
            if($triple->subject->value === $uri)
            {
                $stubinstance->add($triple);
            }
        }

        return $stubinstance;
    }

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
        // URI of a single Dench result
        $uri = 'http://acropolis.org.uk/2cbf4d6fa0db4b58b574c28722e061a7#id';

        // URI of the slot within the proxy RDF
        $slotUri = 'http://acropolis.org.uk/2cbf4d6fa0db4b58b574c28722e061a7.ttl#8bfbb99494594aa3ac21dae6263463a2';

        // slot item URI - we expect this to be passed to fetchAll()
        $slotItemUri = 'http://acropolis.org.uk/8bfbb99494594aa3ac21dae6263463a2#id';

        // stub LOD
        $lod = $this->getMockBuilder(LOD::class)
                    ->setMethods(['__get', 'fetch', 'locate', 'fetchAll'])
                    ->getMock();

        // get for $lod->languages
        $lod->method('__get')
            ->willReturn(array('en'));

        // mock out the RDF fetch for the proxy URI
        $rdf = file_get_contents(FIXTURES_PROXY_DENCH);

        $lod->expects($this->once())
            ->method('fetch')
            ->with($uri)
            ->willReturn($this->createStubLODInstance($lod, $uri, $rdf));

        // mock out the call to locate which returns the LODInstance for the
        // olo:slot
        $lod->expects($this->once())
            ->method('locate')
            ->with($slotUri)
            ->willReturn($this->createStubLODInstance($lod, $slotUri, $rdf));

        // check that the slot item URIs are extracted and passed to fetchAll
        $lod->expects($this->once())
            ->method('fetchAll')
            ->with(array($slotItemUri));

        // stub converter: we don't care what this returns at the moment
        $converter = $this->createMock(RESTopicConverter::class);
        $converter->expects($this->once())
                  ->method('convert')
                  ->with($uri, RESMedia::IMAGE, array($slotItemUri), $lod);

        // meat of the test where we call proxy()
        $client = new RESClient(NULL, $lod, $converter);
        $client->proxy($uri, RESMedia::IMAGE);
    }
}
