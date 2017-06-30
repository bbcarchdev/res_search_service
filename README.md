# RES search service

This is a standalone RESTful web service which provides a convenient way to get
useful data from the RES platform. It acts as a proxy layer on Acropolis,
fetching multiple resources over HTTP and merging them into a single response.
The main use case is as a back-end for media discovery applications.

A simple graphical UI is provided on the root of the application, both for
basic testing and to act as a UI for consuming applications (e.g. the Moodle
plugin). This UI makes Ajax calls to the web service API to get its data.

## Installation

Clone the repo.

Install [Composer](http://getcomposer.org/).

Install [Bower](https://bower.io/).

Run these commands in the root of the project:

```
composer install
bower install
```

If you only want the web service, you can ignore the Bower instructions:
Bower is only necessary if you want to use the graphical UI.

## Running

The simplest way to run the application is with PHP on the command line. Go
to the root of the application and do:

```
php -S localhost:8888 -t .
```

Alternatively, you can run it as a PHP application using a standard web server
like Apache.

If you want to use your own Acropolis endpoint, rather than the public BBC
one at http://acropolis.org.uk/, set an `ACROPOLIS_URL` environment
variable. The web service will then use that as the target endpoint for
the application's HTTP client.

## Paths

The following paths are available on the service:

*   `/?callback=<callback URL>`
    `->` show UI for searching RES and selecting media resources
*   `/api/audiences`
    `->` show Acropolis audiences as JSON
*   `/api/search?q=<search>&limit=<num results>&offset=<zero-indexed offset>&for[]=<audience URI>`
    `->` perform a search and return list of matching topics
    (`for[]=` can be repeated multiple times)
*   `/api/proxy/<proxy ID>?format=json(default)|rdf&media=image|video|audio|text`
    `->` return proxy data, including lists of related media

See the following section for samples of the responses for each.

## Sample responses

### /?callback=<callback URL>

If a `callback` querystring variable is set, the search UI will provide
"Select" buttons next to each resource. Clicking one of these will redirect
the browser to:

```
<callback URL>?media=<JSON-encoded representation of the selected resource>
```

The structure of the selected resource is the same as one of the items
returned by the `/api/proxy` endpoint. For example:

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
redirection.


### /api/audiences

```
# http://localhost:8888/api/audiences
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

### /api/search

NB Only one item is shown in the `items` array here.

The `api_uri` for an item points to the URL on the RES search service from
which full data for the item can be retrieved.

```
# http://localhost:8888/api/search?q=bird
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
      "api_uri": "http://localhost:8888/api/proxy?uri=http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f%23id&media=image"
    },
    ...
  ]
}
```

### /api/proxy

NB Only one player item is shown here.

`players`, `content` and `pages` refer to three different types of resource which are related to the resource:

* `players` are embeddable players for a piece of media. Typically, this means a whole web page containing the media in some kind of player (e.g. Flash, HTML5 media), possibly with playback controls, additional metadata etc. Players may also present a user with an authentication prompt for protected media.
* `content` items are directly-embeddable media. Examples might be URLs for JPEG files or raw audio files (WAV, MP3 etc.). These won't have additional authentication, and their URIs can be used in `<img>`, `<object>` and similar elements in an HTML UI (or their equivalents in non-HTML UIs).
* `page` items are typically web pages containing text which relates to or is about the resource.

If `media=image`, only players with `mediaType=='image'` are shown. Likewise for `audio`, `text` and `video`.

`pages` (web pages relating to the resource) will typically be empty unless `media=text`. Similarly, `players` is likely to be empty if `media=text`.

Consumers of the web service will typically want the `uri` properties for players, content items and pages in the response.

The `thumbnail` property points to a directly-embeddable image which can be used to represent the resource in a consuming application (NB this won't always be set).

See the `lib/RESMedia.php` file for more details of how resources are assigned media types.

```
# http://localhost:8888/api/proxy?uri=http://acropolis.org.uk/10d2e5069bdb457ab0e7ab6da5422e7f%23id&media=image
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

## TODO

Provide a service description of the paths provided by the app (see above).

Separate RESClient code into its own library.

## Author

[Elliot Smith](https://github.com/townxelliot)

## Licence

Copyright © 2017 BBC

The RES search service is licensed under the terms of the Apache License,
Version 2.0 (see LICENCE-APACHE.txt).
