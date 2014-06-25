poi-webservice
==============

an experimental webservice to provide POIs according to the format specification of the FI-WARE POI Data Provider


Usage
==============

* rename config.php.sample to config.php
* adjust configuration
* adjust .htaccess if necessary
* check http://my-poi-provider.url/api/poi/status


API
==============

/get_pois

* poi_id - comma separated list of poi uuids

/bbox_search

* east, west - bounds of longitude in degrees
* south, north - bounds of latitude in degrees
 
/radial_search

* lon - longitude of center in degrees
* lat - latitude of center in degrees
* [optional] radius - distance around center in meters

Common Parameters
==============

* component - comma separated list of valid component names (see /get_components)
* category - comma separated list of categories
* max_results  - maximum result set size

Extensions
==============

* jsoncallback - name of the callback function, switches to jsonp instead of json

Example Queries
==============

* http://130.206.80.175/api/poi/bbox_search?max_results=100&component=fw_core,fw_xml3d&category=HTL&north=52.524759847218&south=52.494252206072&west=13.33817346763&east=13.452242452616

