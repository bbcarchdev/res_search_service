# RES Moodle plugin service

This is a standalone web service which provides a convenient way to get
useful data from the RES platform.

## Paths

*   `/?callback=<callback URL>`
    `->` show URI for searching RES and selecting media resources
*   `/api/audiences`
    `->` show Acropolis audiences as JSON
*   `/api/search?q=<search>&limit=<num results>&offset=<zero-indexed offset>&for[]=<audience URI>`
    `->` perform a search and return list of matching topics
    (`for[]=` can be repeated multiple times)
*   `/api/proxy/<proxy ID>?format=json(default)|rdf&media=image|video|audio|text`
    `->` return proxy data, including lists of related media
