# RES search service

This is a standalone RESTful web service which provides a convenient way to get
useful data from the [Research and Education Space (RES)](http://res.space/)
platform, [Acropolis](http://acropolis.org.uk/). It acts as a proxy layer over
Acropolis, fetching multiple resources over HTTP and merging them into a single
response. The main use case is as a back-end for media discovery applications.

A simple graphical UI is provided on the root of the application, both for
basic testing and to act as a UI for consuming applications (e.g. the
[RES Moodle plugin](https://github.com/bbcarchdev/moodle-repository_res)).
This UI makes Ajax calls to the web service API to get its data.

## Setting up for development

Clone the repo.

Install [Composer](http://getcomposer.org/).

Install [Bower](https://bower.io/).

Run these commands in the root of the project:

```
composer install
bower install
```

If you only want the web service, you can ignore the Bower instructions:
Bower components are only necessary if you want to use the graphical UI.

## Tests

To run the tests, do:

```
./vendor/bin/robo test
```

To run tests with a coverage report, do:

```
./vendor/bin/robo cov
```

## Running

The simplest way to run the application is with PHP on the command line. Go
to the root of the application and do:

```
./vendor/bin/robo server
```

You can access the search application at `http://localhost:8888/`.

Alternatively, you can run the service as a PHP application using a
standard web server like Apache.

If you want to use your own Acropolis endpoint, rather than the public BBC
one at http://acropolis.org.uk/, set an `ACROPOLIS_URL` environment
variable. The web service will then use that as the target endpoint for
the application's HTTP client, e.g.

```
ACROPOLIS_URL=http://localhost:9999/ ./vendor/bin/robo server
```

## Paths

The following paths are available on the service:

*   `/?callback=<callback URL>`

    Display a minimal UI for searching RES and selecting media resources; if
    `callback` is set, media resources can be selected via buttons (see below)

*   `/audiences`

    Show Acropolis audiences as JSON.

*   `/search`

    Perform a search and return list of matching topics. The available parameters
    are:

    *   `q=<search>` (required)

        Find topics with a text label or description containing the string
        `<search>`.

    *   `limit=<num results>`

        Limit the number of results returned to `<num results>`.

    *   `offset=<offset>`

        Show results starting at the offset `<offset>`; `<offset>` is
        zero-indexed.

    *   `for[]=<audience URI>`

        Include results which are only accessible by the audience whose URI
        is `<audience URI>`. The list of available audience URIs is
        accessible at `/audiences`.

        This parameter can be repeated multiple times.

*   `/proxy`

    Return proxy data, including lists of related media, as JSON, for the
    specified proxy URI. The available parameters are:

    *   `uri=<proxy URI>` (required)

        URI of a proxy resource in Acropolis. The RDF for this proxy is
        used to build the JSON response.

    *   `media=image(default)|video|audio|text`

        Include related media resources for the proxy with type matching the
        media type specified.

See the following section for samples of the responses for each.

## Sample responses

### /?callback=<callback URL>

If a `callback` querystring variable is set, the search UI will provide
"Select" buttons next to each resource. Clicking one of these will redirect
the browser to:

```
<callback URL>?media=<URL-encoded JSON representation of the selected resource>
```

The URL-encoding of the JSON for the resource is done by the
[urijs library](https://github.com/medialize/URI.js).

(Note that any existing querystring variables in the callback URL are respected,
so '?' is only appended if it isn't already in the URI; if it is, '&' is
appended instead.)

The structure of the selected resource is the same as one of the items
returned by the `/proxy` endpoint. For example:

```
{
  "sourceUri": "http://bbcimages.acropolis.org.uk/6311090#id",
  "uri": "http://bbcimages.acropolis.org.uk/6311090/player",
  "mediaType": "image",
  "license": "",
  "label": "A Blue Tit  visits a bird feeder",
  "description": "A Blue Tit bird eating nuts from a bird feeder.  Birds feeding garden gardens.",
  "thumbnail": "http://bbcimages.acropolis.org.uk/6311090/media/6311090-200x200.jpeg",
  "date": "2008-09-15",
  "location": "http://sws.geonames.org/2635167/"
}
```

This is JSON-encoded and appended to the callback URL before the browser
is redirected.

### /audiences

```
# http://localhost:8888/audiences
[
  {
    "uri": "http://bbcimages.acropolis.org.uk/#members",
    "label": "Users of the BBC bbcimages Archive Resource"
  },
  {
    "uri": "http://bobnational.net/#members",
    "label": "Authorised users of BoB National"
  },
  {
    "uri": "http://shakespeare.acropolis.org.uk/#members",
    "label": "Authorised users of the BBC Shakespeare Archive Resource"
  },
  {
    "uri": "http://www.bbcredux.com/#members",
    "label": "Authorised users of BBC Redux"
  }
]
```

### /search

Note that only one item is shown in the `items` array here.

The `api_uri` for an item points to the URL on the RES search service from
which full data for the item can be retrieved.

```
# http://localhost:8888/search?q=bird
{
  "acropolis_uri": "http://acropolis.org.uk/?q=bird&media=image&limit=10&offset=0",
  "query": "bird",
  "limit": 10,
  "offset": 0,
  "hasNext": true,
  "items": [
    {
      "topic_uri": "http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f#id",
      "label": "Bird feeder",
      "description": "A birdfeeder, bird feeder, bird table, or tray feeder are devices placed outdoors to supply bird food to birds (bird feeding). The success of a bird feeder in attracting birds depends upon its placement and the kinds of foods offered, as different species have different preferences.\nMost bird feeders supply seeds or bird food, such as millet, sunflower (oil and striped), safflower, Niger seed, and rapeseed or canola seed to seed-eating birds.\nBird feeders often are used for birdwatching and many people keep webcams trained on feeders where birds often congregate some even live just near the bird feeder.",
      "api_uri": "http://localhost:8888/proxy?uri=http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f%23id&media=image"
    },
    ...
  ]
}
```

### /proxy

Note that only one player item is shown here.

`players`, `content` and `pages` refer to three different types of resource which are related to the resource:

* `players` are embeddable players for a piece of media. Typically, this means a whole web page containing the media in some kind of player (e.g. Flash, HTML5 media), possibly with playback controls, additional metadata etc. Players may also present a user with an authentication prompt for protected media.
* `content` items are directly-embeddable media. Examples might be URLs for JPEG files or raw audio files (WAV, MP3 etc.). These shouldn't require additional authentication, and their URIs can be used in `<img>`, `<object>` and similar elements in an HTML UI (or their equivalents in non-HTML UIs).
* `page` items are typically web pages containing text which relates to or is about the resource.

If `media=image`, only players with `mediaType=='image'` are shown. Likewise for `audio`, `text` and `video`.

`pages` (web pages relating to the resource) will typically be empty unless `media=text`. Similarly, `players` is likely to be empty if `media=text`.

Consumers of the web service will typically want the `uri` properties for players, content items and pages in the response.

The `thumbnail` property points to a directly-embeddable image which can be used to represent the resource in a consuming application (which won't always be set).

See the `lib/RESMedia.php` file for more details of how resources are assigned media types.

```
# http://localhost:8888/proxy?uri=http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f%23id&media=image
{
  "uri": "http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f#id",
  "label": "Bird feeder",
  "description": "A birdfeeder, bird feeder, bird table, or tray feeder are devices placed outdoors to supply bird food to birds (bird feeding). The success of a bird feeder in attracting birds depends upon its placement and the kinds of foods offered, as different species have different preferences.\nMost bird feeders supply seeds or bird food, such as millet, sunflower (oil and striped), safflower, Niger seed, and rapeseed or canola seed to seed-eating birds.\nBird feeders often are used for birdwatching and many people keep webcams trained on feeders where birds often congregate some even live just near the bird feeder.",
  "media": "image",
  "players": [
    {
      "sourceUri": "http://bbcimages.acropolis.org.uk/6311090#id",
      "uri": "http://bbcimages.acropolis.org.uk/6311090/player",
      "mediaType": "image",
      "license": "",
      "label": "A Blue Tit  visits a bird feeder",
      "description": "A Blue Tit bird eating nuts from a bird feeder.  Birds feeding garden gardens.",
      "thumbnail": "http://bbcimages.acropolis.org.uk/6311090/media/6311090-200x200.jpeg",
      "date": "2008-09-15",
      "location": "http://sws.geonames.org/2635167/"
    },
    ...
  ],
  "content": [

  ],
  "pages": [

  ]
}
```

## Author

[Elliot Smith](https://github.com/townxelliot) - elliot.smith@bbc.co.uk

## Licence

This project is licensed under the terms of the [Apache License, version 2.0](http://www.apache.org/licenses/LICENSE-2.0).

Copyright © 2017 BBC
