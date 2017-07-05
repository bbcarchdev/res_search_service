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

// set capabilities; this can be overridden in a capabilities.json file
// in this directory if desired
$capabilities = NULL;

$capabilitiesFile = __DIR__ . '/capabilities.json';
if(file_exists($capabilitiesFile))
{
    // if json_decode is passed TRUE as its second argument, JSON is decoded
    // to an associative array rather than a stdClass instance
    $asArray = TRUE;
    $capabilities = json_decode(file_get_contents($capabilitiesFile), $asArray);
}

if(empty($capabilities))
{
    // set default paths for all service URIs
    $apiPrefix = '/';

    $capabilities = array(
        'home' => $apiPrefix,
        'search' => $apiPrefix . 'search',
        'proxy' => $apiPrefix . 'proxy',
        'audiences' => $apiPrefix . 'audiences'
    );
}

// start the app
require_once(__DIR__ . '/vendor/autoload.php');

use \Slim\App as SlimApp;
use \res\libres\RESClient;
use \res\libres\Controller;

// get Acropolis URL from env; if not set, RESClient sets a default
$acropolisUrl = getenv('ACROPOLIS_URL');
$client = new RESClient($acropolisUrl);

$app = new SlimApp();
$container = $app->getContainer();

$container['Controller'] = function($container) use($client, $capabilities) {
    return new Controller($client, $capabilities);
};

$app->get($capabilities['home'], 'Controller:home');
$app->get($capabilities['audiences'], 'Controller:audiences');
$app->get($capabilities['search'], 'Controller:search');
$app->get($capabilities['proxy'], 'Controller:proxy');

$app->run();
