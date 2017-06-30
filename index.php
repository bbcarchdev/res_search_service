<?php
require_once(__DIR__ . '/vendor/autoload.php');

use \Slim\App;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \GuzzleHttp\Psr7\stream_for;

use \res\libres\RESMedia;
use \res\libres\RESClient;

$app = new \Slim\App();

// get Acropolis URL from env; if not set, RESClient sets a default
$acropolisUrl = getenv('ACROPOLIS_URL');

// single HTML page: UI for searching Acropolis, showing search results, and
// displaying a topic with its media;
// call with /?callback=<callback URL>; when a resource is selected, the
// UI is redirected to
// <callback URL>?media=<JSON-encoded representation of the selected resource>
$app->get('/', function (Request $request, Response $response)
{
    $html = file_get_contents(__DIR__ . '/ui.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html')
                    ->withHeader('Content-Location', '/ui.html');
});

// get all audiences known to Acropolis
$app->get('/api/audiences', function(Request $request, Response $response) use($acropolisUrl)
{
    $client = new RESClient($acropolisUrl);
    $result = $client->audiences();
    return $response->withJson($result);
});

// proxy for searches on Acropolis
// call with /api/search?q=<search term>&media=<media type>&limit=<limit>&offset=<offset>&for[]=<audience URI>
// (for[] can be repeated multiple times)
$app->get('/api/search', function(Request $request, Response $response) use($acropolisUrl)
{
    $query = $request->getQueryParam('q', $default=NULL);
    $media = $request->getQueryParam('media', $default=RESMedia::IMAGE);
    $limit = intval($request->getQueryParam('limit', $default=10));
    $offset = intval($request->getQueryParam('offset', $default=0));
    $audiences = $request->getQueryParam('for', $default=NULL);

    $client = new RESClient($acropolisUrl);

    $result = $client->search($query, $media, $limit, $offset, $audiences);

    // for each item in the results, construct a URI pointing at the plugin
    // service API, in the form
    // http://<plugin service domain and port>/api/proxy?uri=<topic URI>
    $baseApiUri = $request->getUri()->withPath('/api/proxy');

    foreach($result['items'] as $index => $item)
    {
        $querystring = 'uri=' . $item['topic_uri'] . '&media=' . $media;
        $item['api_uri'] = "{$baseApiUri->withQuery($querystring)}";
        $result['items'][$index] = $item;
    }

    return $response->withJson($result);
});

// request proxy resource from Acropolis by URI
$app->get('/api/proxy', function(Request $request, Response $response) use($acropolisUrl)
{
    $topicUri = $request->getQueryParam('uri', $default=NULL);
    $media = $request->getQueryParam('media', $default=RESMedia::IMAGE);
    $format = $request->getQueryParam('format', $default='json');

    $client = new RESClient($acropolisUrl);

    $result = $client->proxy($topicUri, $media, $format);

    if($format === 'json')
    {
        return $response->withJson($result);
    }
    else if($format === 'rdf')
    {
        $stream = GuzzleHttp\Psr7\stream_for($result);
        return $response->withBody($stream)
                        ->withHeader('Content-Type', 'text/turtle');
    }
});

$app->run();
