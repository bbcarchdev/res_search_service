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

namespace res\libres;

require_once(__DIR__ . '/../vendor/autoload.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \res\libres\RESMedia;
use \res\libres\RESClient;

class Controller
{
    private $client;

    // map from endpoint names to URLs; this is used in the HTML interface
    // to set search service URLs dynamically
    private $endpoints;

    public function __construct($client, $endpoints)
    {
        $this->client = $client;
        $this->endpoints = $endpoints;
    }

    // single HTML page: UI for searching Acropolis, showing search results, and
    // displaying a topic with its media;
    // call with /?callback=<callback URL>; when a resource is selected, the
    // UI is redirected to
    // <callback URL>?media=<JSON-encoded representation of the selected resource>;
    // endpoints are written into a JavaScript element in the HTML so the
    // UI knows where the service endpoints are (and they don't have to be
    // hard-coded into the UI code)
    public function minimal(Request $request, Response $response)
    {
        $html = file_get_contents(__DIR__ . '/../views/minimal.html');

        $html = preg_replace('/__ENDPOINTS__/', json_encode($this->endpoints), $html);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    // get all audiences known to Acropolis
    public function audiences(Request $request, Response $response)
    {
        $result = $this->client->audiences();
        return $response->withJson($result);
    }

    // proxy for searches on Acropolis
    // call with /api/search?q=<search term>&media=<media type>&limit=<limit>&offset=<offset>&for[]=<audience URI>
    // (for[] can be repeated multiple times)
    public function search(Request $request, Response $response)
    {
        $query = $request->getQueryParam('q', NULL);
        $media = $request->getQueryParam('media', RESMedia::IMAGE);
        $limit = intval($request->getQueryParam('limit', 10));
        $offset = intval($request->getQueryParam('offset', 0));
        $audiences = $request->getQueryParam('for', NULL);

        $result = $this->client->search($query, $media, $limit, $offset, $audiences);

        // for each item in the results, construct a URI pointing at the search
        // service API, in the form
        // http://<search service domain and port>/proxy?uri=<topic URI>
        // (where the piece before the querystring is derived
        // from the endpoints for this Controller)
        $baseApiUri = $this->endpoints['proxy'];

        foreach($result['items'] as $index => $item)
        {
            $querystring = 'uri=' . urlencode($item['topic_uri']) . '&media=' . urlencode($media);
            $item['api_uri'] = $baseApiUri . '?' . $querystring;
            $result['items'][$index] = $item;
        }

        return $response->withJson($result);
    }

    // request proxy resource from Acropolis by URI
    public function proxy(Request $request, Response $response)
    {
        $topicUri = $request->getQueryParam('uri', NULL);
        $media = $request->getQueryParam('media', RESMedia::IMAGE);

        $result = $this->client->proxy($topicUri, $media);

        return $response->withJson($result);
    }
}
