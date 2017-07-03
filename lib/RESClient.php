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

use \res\liblod\LOD;
use \res\liblod\Rdf;
use \res\libres\RESMedia;
use \res\libres\RESLicence;

/**
 * Client for RES (Acropolis).
 * This is a thin wrapper around the existing API for Acropolis which merges
 * multiple requests into one sensible response, and also converts the RDF
 * into arrays which can be encoded to JSON.
 */
class RESClient
{
    const DEFAULT_ACROPOLIS_URL = 'http://acropolis.org.uk/';

    private $acropolisUrl;

    /**
     * Constructor.
     *
     * @param string $acropolisUrl
     * @param \res\liblod\LOD $lod
     */
    public function __construct($acropolisUrl, $lod=NULL)
    {
        if(empty($acropolisUrl))
        {
            $acropolisUrl = RESClient::DEFAULT_ACROPOLIS_URL;
        }
        $this->acropolisUrl = $acropolisUrl;

        if(empty($lod))
        {
            $lod = new LOD();
        }
        $this->lod = $lod;
    }

    /**
     * Fetch /audiences URI from RES
     * TODO may be multiple pages of audiences in future, so may need to revisit
     */
    public function audiences()
    {
        $uri = rtrim($this->acropolisUrl, '/') . '/audiences';
        $audiencesInstance = $this->lod[$uri];

        $audiences = array();

        foreach($audiencesInstance['rdfs:seeAlso'] as $audienceObject)
        {
            $audienceUri = "$audienceObject";
            $label = "{$this->lod[$audienceUri]['rdfs:label']}";

            if($label !== 'Everyone')
            {
                $audiences[] = array(
                    'uri' => $audienceUri,
                    'label' => $label
                );
            }
        }

        return $audiences;
    }

    /**
     * Search RES for topics with related media.
     *
     * @param string $query Key words to search for
     * @param string $media Media filter for search; one of
     * RESMedia::AUDIO, RESMedia::IMAGE, RESMedia::TEXT or RESMedia::VIDEO
     * @param int $limit Number of results to return
     * @param int $offset Zero-indexed position of first result to return
     * @param array $audiences An array of recognised Acropolis audience URIs
     *
     * @return array
     */
    public function search($query, $media, $limit=10, $offset=0, $audiences=NULL)
    {
        $result = array(
            'acropolis_uri' => NULL,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
            'hasNext' => FALSE,
            'items' => array()
        );

        if($query)
        {
            $uri = $this->acropolisUrl .
                   '?q=' . urlencode($query) .
                   '&media=' . urlencode($media) .
                   '&limit=' . urlencode($limit) .
                   '&offset=' . urlencode($offset);

            if(is_array($audiences))
            {
                $audiencesQuery = '';
                foreach($audiences as $audience)
                {
                    if($audiencesQuery !== '')
                    {
                        $audiencesQuery .= '&';
                    }
                    $audiencesQuery .= 'for=' . urlencode($audience);
                }

                $uri .= '&' . $audiencesQuery;
            }

            $result['acropolis_uri'] = $uri;

            $searchResultResource = $this->lod[$uri];

            foreach($searchResultResource['olo:slot'] as $slot)
            {
                $slotUri = $slot->value;
                $slotResource = $this->lod[$slotUri];

                // if we can't resolve the slot resource, don't do anything
                if(!$slotResource)
                {
                    continue;
                }

                foreach($slotResource['olo:item'] as $slotItem)
                {
                    if($slotItem->isResource())
                    {
                        $topic = $this->lod[$slotItem->value];

                        $label = "{$topic['dcterms:title,rdfs:label']}";

                        // reject any foaf:Document resources whose label starts
                        // with "Information about" - these are useless
                        $isInfo = preg_match('|^Information about |', $label);
                        if($topic->hasType('foaf:Document') && $isInfo)
                        {
                            continue;
                        }

                        $desc = "{$topic['dcterms:description,rdfs:comment']}";

                        $result['items'][] = array(
                            'topic_uri' => $topic->uri,
                            'label' => $label,
                            'description' => $desc
                        );
                    }
                }
            }
        }

        // do we have more results? (yes if xhtml:next statement present)
        $result['hasNext'] = !empty("{$searchResultResource['xhtml:next']}");

        return $result;
    }

    /**
     * Fetch data about a single proxy URI and its olo:slot resources.
     * Convert into an associative array about the proxy and its media.
     * NB this has to follow an inference chain and do lots of fetches to
     * get enough data to populate the Moodle UI properly.
     *
     * @param string $media Type of media to restrict results to; one of
     * 'image', 'video', 'text' or 'audio'
     * @param string $format Return format 'json' (return convenient JSON
     * representation) or 'rdf' (get raw RDF for all relevant resources)
     *
     * @return mixed Array suitable for JSON encoding or Turtle string
     */
    public function proxy($proxyUri, $media, $format='json')
    {
        $proxy = $this->lod->fetch($proxyUri);

        if(!$proxy)
        {
            return ($format === 'json' ? NULL : '');
        }

        // find all the resources which could be useful;
        // if the proxy has olo:slot resources, we want their olo:items
        $slotItemUris = array();
        $slotObjectResources = $proxy['olo:slot'];

        foreach($slotObjectResources as $slotObjectResource)
        {
            $slotResourceUri = "$slotObjectResource";
            $slotResource = $this->lod[$slotResourceUri];
            $slotItemUris[] = "{$slotResource['olo:item']}";
        }

        // fetch the slot resources; we need these to be able to get the
        // players
        $this->lod->fetchAll($slotItemUris);

        // if the format is RDF, return the whole LOD object as Turtle
        // (mostly useful for dev)
        if($format === 'rdf')
        {
            return Rdf::toTurtle($this->lod);
        }
        else
        {
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
                $sameAsSlotItemUris = $this->lod->getSameAs($slotItemUri);

                // also get the topics or primary topics of the resources which
                // are sameAs the slot item
                $topicUris = array();
                $topicPredicates = 'foaf:topic,foaf:primaryTopic,schema:about';
                $licensePredicates = 'cc:license,dcterms:license,' .
                                     'dcterms:rights,dcterms:accessRights,' .
                                     'xhtml:license';

                foreach($sameAsSlotItemUris as $sameAsSlotItemUri)
                {
                    $sameAsResource = $this->lod[$sameAsSlotItemUri];
                    $topicUris[] = "{$sameAsResource[$topicPredicates]}";
                }

                $possibleMediaUris = array_merge($sameAsSlotItemUris, $topicUris);
                foreach($possibleMediaUris as $possibleMediaUri)
                {
                    $resource = $this->lod[$possibleMediaUri];

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
}
