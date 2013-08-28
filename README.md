poi-webservice
==============

an experimental webservice to provide POIs according to the format specification of [[http://github.com/stlemme/poi]]


Usage
==============

* rename config.php.sample to config.php
* adjust configuration
* adjust .htaccess
* Service Point: http://my-poi-provider.url/api/poi/


API
==============
id  --   http://my-poi-provider.url/api/poi/?id=5540b5d2-0909-4b0a-8a69-5ec3a64dc3d7
  - returns a result set with one specific entry (Hotel Continental Saarbrücken)

tag  --   http://my-poi-provider.url/api/poi/?tag=geonames.org_feature_code_HTL
  - returns a result set with 25 (default) of about 3400 hotels

limit  --  http://my-poi-provider.url/api/poi/?tag=geonames.org_feature_code_HTL&limit=100
  - returns a result set with 100 of about 3400 hotels

bbox  --   http://my-poi-provider.url/api/poi/?bbox=6.9831160640714,49.232331624725,7.0116118526456,49.239701237993
  - returns a result set with 25 (default) pois within the bounding box

position  --  http://my-poi-provider.url/api/poi/?id=5540b5d2-0909-4b0a-8a69-5ec3a64dc3d7&lat=49.257187&long=7.042128
  - returns a result set with one specific entry (Hotel Continental Saarbrücken)
  - the entry contains meta data about the distance from the given position

dist  --  http://my-poi-provider.url/api/poi/?tag=geonames.org_feature_code_HTL&lat=49.257187&long=7.042128&dist=6000
  - returns a result set with 7 hotels near the DFKI
  - each entry contains meta data about the distance from the given position
  - each entry is less than 6000 meters away from the given position

