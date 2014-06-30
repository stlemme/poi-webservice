
import http.client
import json


host = "localhost"
api = "/api/poi/admin/configure"

header = {
	"Content-Type" : "application/json",
	"User-Agent" : "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
	'Accept' : 'application/json'
}


if __name__ == '__main__':
	conn = http.client.HTTPConnection(host)
	
	data = input('Enter config change: ')

	conn.request('POST', api, data, header)
	response = conn.getresponse()
	
	if response.status != 200:
		print('An error occured:', response.reason)
	
	data = response.read()
	
	config = json.loads(data.decode('utf-8'))
	
	print()
	print(json.dumps(config, indent=4))
	
	conn.close()
