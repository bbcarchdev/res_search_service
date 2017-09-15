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

/* Class for converting licence URIs to short text form */
class RESLicence
{
    const LICENCE_MAP = array(
        'http://bbcarchdev.github.io/licences/dps/1.0#id' => 'BBC DPS',
        'http://creativecommons.org/publicdomain/zero/1.0/' => 'CC0 1.0',
        'http://creativecommons.org/licenses/by/1.0/' => 'CC BY 1.0',
        'https://creativecommons.org/licenses/by/1.0/' => 'CC BY 1.0',
        'http://creativecommons.org/licenses/by-sa/1.0/' => 'CC BY-SA 1.0',
        'https://creativecommons.org/licenses/by-sa/1.0/' => 'CC BY-SA 1.0',
        'http://creativecommons.org/licenses/by/2.5/' => 'CC BY 2.5',
        'https://creativecommons.org/licenses/by/2.5/' => 'CC BY 2.5',
        'http://creativecommons.org/licenses/by-sa/2.5/' => 'CC BY-SA 2.5',
        'https://creativecommons.org/licenses/by-sa/2.5/' => 'CC BY-SA 2.5',
        'http://creativecommons.org/licenses/by/3.0/' => 'CC BY 3.0',
        'https://creativecommons.org/licenses/by/3.0/' => 'CC BY 3.0',
        'http://creativecommons.org/licenses/by-sa/3.0/' => 'CC BY-SA 3.0',
        'https://creativecommons.org/licenses/by-sa/3.0/' => 'CC BY-SA 3.0',
        'http://creativecommons.org/licenses/by/3.0/us/' => 'CC BY 3.0 US',
        'https://creativecommons.org/licenses/by/3.0/us/' => 'CC BY 3.0 US',
        'http://creativecommons.org/licenses/by-sa/3.0/us/' => 'CC BY-SA 3.0 US',
        'https://creativecommons.org/licenses/by-sa/3.0/us/' => 'CC BY-SA 3.0 US',
        'http://creativecommons.org/licenses/by/4.0/' => 'CC BY 4.0',
        'https://creativecommons.org/licenses/by/4.0/' => 'CC BY 4.0',
        'http://creativecommons.org/licenses/by-sa/4.0/' => 'CC BY-SA 4.0',
        'https://creativecommons.org/licenses/by-sa/4.0/' => 'CC BY-SA 4.0',
        'http://collection.britishmuseum.org/licensing.html' => 'CC BY-NC-SA 4.0',
        'http://id.loc.gov/about/' => 'LoC',
        'http://opendatacommons.org/licenses/pddl/1.0/' => 'ODC PDDL v1.0',
        'http://opendatacommons.org/licenses/by/1.0/' => 'ODC-By v1.0',
        'http://opendatacommons.org/licenses/odbl/1.0/' => 'ODbL v1.0',
        'https://www.nationalarchives.gov.uk/doc/open-government-licence/version/1/' => 'OGL v1',
        'http://www.nationalarchives.gov.uk/doc/open-government-licence/version/1/' => 'OGL v1',
        'https://www.nationalarchives.gov.uk/doc/open-government-licence/version/2/' => 'OGL v2',
        'http://www.nationalarchives.gov.uk/doc/open-government-licence/version/2/' => 'OGL v2',
        'https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/' => 'OGL v3',
        'http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/' => 'OGL v3',
        'http://reference.data.gov.uk/id/open-government-licence' => 'OGL v3',
        'http://www.ordnancesurvey.co.uk/business-and-government/licensing/using-creating-data-with-os-products/os-opendata.html' => 'OS OpenData',
        'http://www.ordnancesurvey.co.uk/oswebsite/opendata/licence/docs/licence.pdf' => 'OS OpenData'
    );

    /**
     * Get the short string representing a licence.
     *
     * @param string $licenceUri Licence URI to get short form for
     */
    public static function getShortForm($licenceUri)
    {
        if(isset(self::LICENCE_MAP[$licenceUri]))
        {
            return self::LICENCE_MAP[$licenceUri];
        }
        else
        {
            return '';
        }
    }

    /**
     * Get a comma-separated string of licensing predicates in CURIE form
     * which RES is happy with.
     */
    public static function getLicensingPredicates()
    {
        return implode(
          ',',
          array(
              'cc:license',
              'dcterms:license',
              'dcterms:rights',
              'dcterms:accessRights',
              'xhtml:license'
          )
        );
    }
}
