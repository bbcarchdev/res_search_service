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

/**
 * Class for working with RES media types
 */
class RESMedia
{
    const VIDEO = 'video';
    const AUDIO = 'audio';
    const IMAGE = 'image';
    const TEXT = 'text';

    private static function isVideo($lodinstance, $mediaUri)
    {
        return (strpos($mediaUri, '.youtube.') !== FALSE) ||
            $lodinstance->hasType(
                'dcmitype:MovingImage',
                'schema:Movie',
                'schema:VideoObject',
                'po:TVContent'
            );
    }

    private static function isAudio($lodinstance, $mediaUri)
    {
        return $lodinstance->hasType(
            'dcmitype:Sound',
            'schema:AudioObject',
            'po:RadioContent'
        );
    }

    private static function isImage($lodinstance, $mediaUri)
    {
        return $lodinstance->hasType(
            'dcmitype:StillImage',
            'schema:Photograph',
            'schema:ImageObject',
            'foaf:Image'
        );
    }

    private static function isText($lodinstance, $mediaUri)
    {
        return $lodinstance->hasType(
            'dcmitype:Text'
        );
    }

    /**
     * Determine the media type of a LODInstance.
     *
     * @param \bbcarchdev\liblod\LODInstance
     * @param string $mediaUri URI of the media player or content
     *
     * @return string Media type if the LODInstance $lodinstance has
     * rdf:type <media type>, where <media type> is a recognisable media RDF
     * type; NULL otherwise; media type return value is one of
     * 'audio', 'video', 'image', 'text'
     */
    public static function getMediaType($lodinstance, $mediaUri='')
    {
        // early return if $lodinstance is not set
        if(empty($lodinstance))
        {
            return NULL;
        }

        if(self::isVideo($lodinstance, $mediaUri))
        {
            return self::VIDEO;
        }
        else if(self::isAudio($lodinstance, $mediaUri))
        {
            return self::AUDIO;
        }
        else if(self::isImage($lodinstance, $mediaUri))
        {
            return self::IMAGE;
        }
        else if(self::isText($lodinstance, $mediaUri))
        {
            return self::TEXT;
        }

        return NULL;
    }
}
