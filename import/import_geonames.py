

import csv
import urllib2
import httplib
import datetime
import json
import uuid
import sys


csvfile = open("DE.txt", "rb")
host = "my-poi-provider.url"
api = "/api/poi/"


fieldnames = [
	"geonameid",
	"name",
	"asciiname",
	"alternatenames",
	"latitude",
	"longitude",
	"feature_class",
	"feature_code",
	"country_code",
	"cc2",
	"admin1_code",
	"admin2_code",
	"admin3_code",
	"admin4_code",
	"population",
	"elevation",
	"dem",
	"timezone",
	"modification_date"
]

filereader = csv.DictReader(csvfile, fieldnames, delimiter='\t')

header = {
	"Content-Type" : "application/json",
	"User-Agent" : "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
	'Accept' : 'application/json'
}

conn = httplib.HTTPConnection(host)

stat = {
	"overall" : 0,
	"success" : 0,
	"failed" : 0
}


for row in filereader:
	
	stat["overall"] += 1
	
	# print row
	# print
	# print
	
	wgs84feature = {
		"method" : "wgs84",
		"long" : float(row["longitude"]),
		"lat" : float(row["latitude"]),
		"transformation" : {
			"matrix" : [1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0]
		}
	}
	
	if len(row["elevation"]) > 0:
		wgs84feature["elevation"] = float(row["elevation"]);
	
	poi = {
		# "id" : str(uuid.uuid4()),
		
		"structure" : {
			"bounds" : {
				"center" : [0.0, 0.0],
				"radius" : 0.0
			},
			"navigation_points" : [
				{
					"type" : "mainpoint",
					"coordinates" : [0.0, 0.0]
				}
			]
		},
		
		"features" : [
			wgs84feature
		],
		
		"contents" : [
			{
				"type" : "name",
				"name" : row["name"]
			},
			{
				"type" : "label",
				"label" : row["name"]
			},
			{
				"type" : "tag",
				"name" : "geonames.org_feature_class_" + row["feature_class"],
				"hashtag" : True
			},
			{
				"type" : "tag",
				"name" : "geonames.org_feature_code_" + row["feature_code"],
				"hashtag" : True
			}
		],
		
		"relations" : [
		],
		
		"source" : {
			"author" : "stefan",
			"publisher" : "geonames.org",
			"created" : row["modification_date"],
			"updated" : row["modification_date"]
		}
	}
	
	data = json.dumps(poi)
	# print data
	# print
	# print
	
	conn.request("POST", api, data, header)
	response = conn.getresponse()
	if response.status != 201:
		stat["failed"] += 1
		
		print
		print response.status, response.reason
		print response.read()
		print
		print
		print
		
		continue
		
	stat["success"] += 1
	
	if (stat["success"] % 1000 == 0):
		sys.stdout.write('.')
	
	
conn.close()

print "Statistic:", stat["success"], "succeeded", "out of", stat["overall"], "(overall)", "with", stat["failed"], "failed"
print
print
