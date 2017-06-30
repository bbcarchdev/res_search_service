<?php
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

    private function isVideo($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:MovingImage',
            'schema:Movie',
            'schema:VideoObject',
            'po:TVContent'
        );
    }

    private function isAudio($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:Sound',
            'schema:AudioObject',
            'po:RadioContent'
        );
    }

    private function isImage($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:StillImage',
            'schema:Photograph',
            'schema:ImageObject',
            'foaf:Image'
        );
    }

    private function isText($lodinstance)
    {
        return $lodinstance->hasType(
            'dcmitype:Text'
        );
    }

    /**
     * Determine the media type of a LODInstance.
     *
     * @param \res\liblod\LODInstance
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

        if($this->isVideo($lodinstance))
        {
            return self::VIDEO;
        }
        else if($this->isAudio($lodinstance))
        {
            return self::AUDIO;
        }
        else if($this->isImage($lodinstance))
        {
            return self::IMAGE;
        }
        else if($this->isText($lodinstance))
        {
            return self::TEXT;
        }

        return NULL;
    }
}
