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

use \bbcarchdev\liblod\LOD;
use \bbcarchdev\liblod\Rdf;
use \res\libres\RESTopicConverter;

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
    private $lod;
    private $converter;

    /**
     * Constructor.
     *
     * @param string $acropolisUrl
     * @param \bbcarchdev\liblod\LOD $lod
     * @param \res\libres\RESTopicConverter $converter
     */
    public function __construct($acropolisUrl=NULL, $lod=NULL, $converter=NULL)
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

        if(empty($converter))
        {
            $converter = new RESTopicConverter($this->lod);
        }
        $this->converter = $converter;
    }

    /**
     * Fetch /audiences URI from RES
     * TODO may be multiple pages of audiences in future, so may need to revisit
     */
    public function audiences()
    {
        $uri = rtrim($this->acropolisUrl, '/') . '/audiences';
        $audiencesInstance = $this->lod->resolve($uri);

        $audiences = array();

        foreach($audiencesInstance->filter('rdfs:seeAlso') as $audienceObject)
        {
            $audienceUri = "$audienceObject";
            $audience = $this->lod->locate($audienceUri);
            $label = "{$audience->filter('rdfs:label')}";

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
            // build URI
            $uri = $this->acropolisUrl . '?';

            if(is_array($audiences))
            {
                // the audiences array has to be sorted, as Acropolis sorts
                // the querystring in the returned resource's URI alphabetically
                $audiencesQuery = '';
                sort($audiences);
                foreach($audiences as $audience)
                {
                    if($audiencesQuery !== '')
                    {
                        $audiencesQuery .= '&';
                    }

                    // only the fragment identifier needs to be URL-encoded:
                    // if the whole audience URI is encoded, the search result
                    // returned by RES has a URI which has un-encoded audience
                    // URIs in its querystring (except for the '#' which stays
                    // as '%23'), so you can't find it
                    $audiencesQuery .= 'for=' . str_replace('#', '%23', $audience);
                }

                $uri .= $audiencesQuery . '&';
            }

            $uri .= 'limit=' . urlencode($limit) .
                    '&media=' . urlencode($media) ;

            if($offset > 0)
            {
                $uri .= '&offset=' . urlencode($offset);
            }

            $uri .= '&q=' . urlencode($query);

            $result['acropolis_uri'] = $uri;

            // resolve the URI
            $this->lod->fetch($uri);
            $searchResultResource = $this->lod->locate($uri);

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

            // do we have more results? (yes if xhtml:next statement present)
            $result['hasNext'] = !empty("{$searchResultResource['xhtml:next']}");
        }

        return $result;
    }

    /**
     * Fetch data about a single proxy URI and its olo:slot resources.
     * Convert into an associative array about the proxy and its media.
     * NB this has to follow an inference chain and do lots of fetches to
     * get enough data to populate the Moodle UI properly.
     *
     * @param string $proxyUri URI of Acropolis proxy resource to fetch
     * @param string $media Type of media to restrict results to; one of
     * 'image', 'video', 'text' or 'audio'
     *
     * @return mixed Array suitable for JSON encoding
     */
    public function proxy($proxyUri, $media)
    {
        $proxy = $this->lod->fetch($proxyUri);

        if(!$proxy)
        {
            return NULL;
        }

        // find all the resources which could be useful;
        // if the proxy has olo:slot resources, we want their olo:items
        $slotItemUris = array();
        $slotObjectResources = $proxy->filter('olo:slot');

        foreach($slotObjectResources as $slotObjectResource)
        {
            $slotResourceUri = "$slotObjectResource";
            $slotResource = $this->lod->locate($slotResourceUri);
            $slotItemUris[] = "{$slotResource['olo:item']}";
        }

        // fetch the slot resources; we need these to be able to get the
        // players
        $this->lod->fetchAll($slotItemUris);

        return $this->converter->convert($proxyUri, $media, $slotItemUris);
    }
}
