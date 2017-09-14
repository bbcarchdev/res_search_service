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

    private static function isVideo($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:MovingImage',
            'schema:Movie',
            'schema:VideoObject',
            'po:TVContent'
        );
    }

    private static function isAudio($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:Sound',
            'schema:AudioObject',
            'po:RadioContent'
        );
    }

    private static function isImage($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:StillImage',
            'schema:Photograph',
            'schema:ImageObject',
            'foaf:Image'
        );
    }

    private static function isText($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:Text'
        );
    }

    /**
     * Determine the media type of a LODInstance.
     *
     * @param \bbcarchdev\liblod\LODInstance
     *
     * @return string Media type if the LODInstance $lodinstance has
     * rdf:type <media type>, where <media type> is a recognisable media RDF
     * type; NULL otherwise; media type return value is one of
     * 'audio', 'video', 'image', 'text'
     */
    public static function getMediaType($lodinstance)
    {
        // early return if $lodinstance is not set
        if(empty($lodinstance))
        {
            return NULL;
        }

        if(self::isVideo($lodinstance))
        {
            return self::VIDEO;
        }
        else if(self::isAudio($lodinstance))
        {
            return self::AUDIO;
        }
        else if(self::isImage($lodinstance))
        {
            return self::IMAGE;
        }
        else if(self::isText($lodinstance))
        {
            return self::TEXT;
        }

        return NULL;
    }
}
