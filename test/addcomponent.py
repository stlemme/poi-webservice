
import http.client
import json
import sys


host = "localhost"
api = "/api/poi/admin/register_component"

header = {
	"Content-Type" : "application/json",
	"User-Agent" : "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
	'Accept' : 'application/json'
}


if __name__ == '__main__':
	conn = http.client.HTTPConnection(host)
	
	if len(sys.argv) < 2:
		sys.exit("provide json schema file as argument")
	
	filename = sys.argv[1]
	
	with open(filename, 'r') as fp:
		data = fp.read()

	conn.request('POST', api, data, header)
	response = conn.getresponse()
	
	if response.status != 200:
		print('An error occured:', response.reason)
	
	data = response.read()
	
	data = data.decode('utf-8')
	
	print()
	print(data)
	
	conn.close()
