from twython import Twython
import json

from obtainauth import(
    ACCESS_TOKEN
)

from auth import(
    consumer_key
)
twitter = Twython(consumer_key, access_token=ACCESS_TOKEN)
b = twitter.search(q='@SunderlandAFC')
#dictionary = a.json()
print(b)
'''
with open ('outJSONTest.json','W') as f:
    json.dumps(dictionary, f)
'''

