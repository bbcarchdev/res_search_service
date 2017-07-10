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
        $this->assertSame(json_encode($audiences, TRUE), $body);
    }
}
