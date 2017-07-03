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

use \res\libres\RESLicence;
use \res\libres\RESMedia;

/**
 * Converts RES topic RDF to JSON.
 */
class RESTopicConverter
{
    /**
     * Convert RDF stored in a LOD context to JSON.
     *
     * @param string $proxyUri URI of the proxy resource on Acropolis which
     * represents the topic
     * @param \res\liblod\LOD $lod
     *
     * @return mixed JSON object representing the topic and its media
     */
    public function convert($proxyUri, $lod)
    {
        $proxy = $lod->locate[$proxyUri];

        $proxyLabel = "{$proxy['rdfs:label,dcterms:title']}";
        $proxyDescription = "{$proxy['dcterms:description,rdfs:comment,po:synopsis']}";

        // convert relevant resources to JSON
        $pages = array();
        $players = array();
        $content = array();

        // extract web pages, only from the proxy itself
        if($media === 'text')
        {
            foreach($proxy['foaf:page'] as $page)
            {
                $pageUri = "{$page->value}";

                // ignore non-HTTP URIs
                if(substr($pageUri, 0, 4) === 'http')
                {
                    $pages[] = array(
                        'source_uri' => $proxyUri,
                        'uri' => $pageUri,
                        'label' => $proxyLabel,
                        'mediaType' => 'web page'
                    );
                }
            }
        }

        // extract players and content via olo:slot->olo:item
        foreach($slotItemUris as $slotItemUri)
        {
            // retrieve the URIs of the media which are same as the slot item
            // ($slotItemUri is a RES proxy URI, so this gives us the URI
            // of the original resource)
            $sameAsSlotItemUris = $lod->getSameAs($slotItemUri);

            // also get the topics or primary topics of the resources which
            // are sameAs the slot item
            $topicUris = array();
            $topicPredicates = 'foaf:topic,foaf:primaryTopic,schema:about';
            $licensePredicates = 'cc:license,dcterms:license,' .
                                 'dcterms:rights,dcterms:accessRights,' .
                                 'xhtml:license';

            foreach($sameAsSlotItemUris as $sameAsSlotItemUri)
            {
                $sameAsResource = $lod[$sameAsSlotItemUri];
                $topicUris[] = "{$sameAsResource[$topicPredicates]}";
            }

            $possibleMediaUris = array_merge($sameAsSlotItemUris, $topicUris);
            foreach($possibleMediaUris as $possibleMediaUri)
            {
                $resource = $lod[$possibleMediaUri];

                if(empty($resource))
                {
                    continue;
                }

                // if it's got an mrss:player or mrss:content, we want it;
                // but we reject any resources which don't match the media
                // type filter (if set)
                foreach($resource['mrss:player,mrss:content'] as $mediaUri)
                {
                    $mediaType = RESMedia::getMediaType($resource);

                    if($mediaType === $media)
                    {
                        $licence = "{$resource[$licensePredicates]}";
                        if(!empty($licence))
                        {
                            $licence = RESLicence::getShortForm($licence);
                        }

                        $players[] = array(
                            'sourceUri' => $possibleMediaUri,
                            'uri' => "$mediaUri",
                            'mediaType' => $mediaType,
                            'license' => $licence,
                            'label' => "{$resource['dcterms:title,rdfs:label']}",
                            'description' => "{$resource['dcterms:description,rdfs:comment']}",
                            'thumbnail' => "{$resource['schema:thumbnailUrl']}",
                            'date' => "{$resource['dcterms:date']}",
                            'location' => "{$resource['lio:location']}"
                        );
                    }
                }
            }
        }

        return array(
            'uri' => "$proxyUri",
            'label' => $proxyLabel,
            'description' => $proxyDescription,
            'media' => $media,
            'players' => $players,
            'content' => $content,
            'pages' => $pages
        );
    }
}
