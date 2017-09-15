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

// set default paths for all service URIs
$apiPrefix = '/';

$paths = array(
    'minimal' => $apiPrefix . 'minimal',
    'search' => $apiPrefix . 'search',
    'proxy' => $apiPrefix . 'proxy',
    'audiences' => $apiPrefix . 'audiences'
);

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

$container['Controller'] = function($container) use($client, $paths) {
    return new Controller($client, $paths);
};

$app->get($apiPrefix, 'Controller:minimal');
$app->get($paths['minimal'], 'Controller:minimal');
$app->get($paths['audiences'], 'Controller:audiences');
$app->get($paths['search'], 'Controller:search');
$app->get($paths['proxy'], 'Controller:proxy');

$app->run();
