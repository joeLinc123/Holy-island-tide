from twython import Twython

from auth import(
    consumer_key,
    consumer_secret
)

twitter = Twython(consumer_key,consumer_secret, oauth_version=2)
ACCESS_TOKEN = twitter.obtain_access_token()
print(ACCESS_TOKEN)





