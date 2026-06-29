#!/usr/local/bin/python3

import requests
import json
import sys

#Add in the latitude and longitude from the query

latitude = sys.argv[1]
longtitude = sys.argv[2]


#Create lists for the distance and time
overallDistance = []
overallTime = []


#process a set of segments
def processSegmentsList(withSegments):

    for segments in withSegments:

        processSegmentsDictionary(segments) #Pass in the dictionary


#Process each dictionary in the segment
def processSegmentsDictionary(withSegmentsDictionary):

    for segmentsKey, segmentsValue in withSegmentsDictionary.items():

        #Now can access the key and value pairs in the services dictionary - We can save the distance and duration

        if segmentsKey == "distance":

            overallDistance.append(segmentsValue)
        
        if segmentsKey == "duration":
            overallTime.append(segmentsValue)

#Output the details of the extract
def outputDetails():

    length = len(overallDistance)

    for i in range(length):
        print(overallDistance[i])
        print(",")
        print(overallTime[i])

#Formulate request
headers = {
    'Accept': 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
}

#Input the requests url, this includes the latitudes and longitudes from above, as well as the end coordinates which are the ones for holy island
request = 'https://api.openrouteservice.org/v2/directions/driving-car?api_key=5b3ce3597851110001cf6248eed8147df04b428eaf88d9021ad70954&start=' + longtitude + ',' + latitude + '&end=-1.875323,55.677739'

#Perform the get requests action
request1 = requests.get(request, headers=headers)
print(request1.status_code)
print(",")
print(request1.reason)
print(",")


#Get the text version of the returned data
feed = request1.text
#Load into JSON dictionary for parse
route_dict = json.loads(feed)

#This is used to determine what the JSON looks like, so it can be parsed
'''
with open('route_dict.txt','w') as json_file:
    json_file.write(json.dumps(route_dict, indent=4, sort_keys=True))
'''

#Upon analysis of the route_dict data, can determine that the data needed is in the 1st array of the features key under properties.
prop_dict = route_dict['features'][0]['properties']
'''
with open('propdict.Json', 'w') as json_file:
    json_file.write(json.dumps(prop_dict, indent=4, sort_keys=True))
'''

#Properties key is a dictionary, so needs to be read as such
for key, value in prop_dict.items():
    #Looking for a key of segments to get the data needed. Where that key is found process the value
    if key == "segments":
        processSegmentsList(value)

#Send the data back to the calling php.
outputDetails()