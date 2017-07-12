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

use \Slim\Http\Environment;
use \Slim\Http\Request;
use \Slim\Http\Response;

use res\libres\RESClient;
use res\libres\Controller;

final class ControllerTest extends TestCase
{
    private $capabilities = array(
        'minimal' => '/minimal',
        'search' => '/search',
        'proxy' => '/proxy',
        'audiences' => '/audiences'
    );

    public function testMinimal()
    {
        // we expect this capabilities string to be encoded into the HTML page
        $expectedCapabilitiesString = json_encode($this->capabilities);

        $environment = Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/minimal'
        ));
        $request = Request::createFromEnvironment($environment);
        $response = new Response();

        $client = $this->getMockBuilder(RESClient::class)->getMock();
        $controller = new Controller($client, $this->capabilities);

        $response = $controller->minimal($request, $response);
        $body = (string)$response->getBody();
        $this->assertContains($expectedCapabilitiesString, $body);
    }

    public function testAudiences()
    {
        $audiences = array(
            array(
                'uri' => 'http://foo.bar/audience1',
                'label' => 'People with permission A'
            ),
            array(
                'uri' => 'http://foo.bar/audience2',
                'label' => 'People with permission B'
            )
        );

        $client = $this->getMockBuilder(RESClient::class)
                       ->setMethods(['audiences'])
                       ->getMock();

        $client->expects($this->once())
               ->method('audiences')
               ->willReturn($audiences);

        $environment = Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/audiences'
        ));
        $request = Request::createFromEnvironment($environment);
        $response = new Response();

        $controller = new Controller($client, $this->capabilities);

        $response = $controller->audiences($request, $response);
        $body = (string)$response->getBody();
        $this->assertJsonStringEqualsJsonString(json_encode($audiences, TRUE), $body);
    }

    public function testProxy()
    {
        $topicUri = 'http://foo.bar/topic1';

        $proxyBody = array(
            'acropolis_uri' => $topicUri
        );

        $client = $this->getMockBuilder(RESClient::class)
                       ->setMethods(['proxy'])
                       ->getMock();

        $client->expects($this->once())
               ->method('proxy')
               ->with($topicUri, 'video')
               ->willReturn($proxyBody);

        $environment = Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/proxy?media=video&uri=' . $topicUri
        ));
        $request = Request::createFromEnvironment($environment);
        $response = new Response();

        $controller = new Controller($client, $this->capabilities);

        $response = $controller->proxy($request, $response);
        $body = (string)$response->getBody();
        $this->assertJsonStringEqualsJsonString(json_encode($proxyBody, TRUE), $body);
    }

    public function testSearch()
    {
        $searchBody = array(
            'items' => array(
                array(
                    'topic_uri' => 'http://foo.bar/topic1'
                )
            )
        );

        $query = 'tench';
        $media = 'video';
        $limit = 5;
        $offset = 10;
        $audiences = array('http://foo.bar/audience1', 'http://foo.bar/audience2');

        $client = $this->getMockBuilder(RESClient::class)
                       ->setMethods(['search'])
                       ->getMock();

        $client->expects($this->once())
               ->method('search')
               ->with($query, $media, $limit, $offset, $audiences)
               ->willReturn($searchBody);

        $qstring = "?q=$query&media=$media&limit=$limit&offset=$offset&for[]=" .
                   implode($audiences, '&for[]=');

        $environment = Environment::mock(array(
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search' . $qstring
        ));
        $request = Request::createFromEnvironment($environment);
        $response = new Response();

        $controller = new Controller($client, $this->capabilities);

        $response = $controller->search($request, $response);

        $body = json_decode($response->getBody(), TRUE);
        $item = $body['items'][0];

        $expectedApiUri = 'http://localhost/proxy?uri=http://foo.bar/topic1&media=video';
        $actualApiUri = $item['api_uri'];
        $this->assertEquals($expectedApiUri, $actualApiUri);
    }
}
