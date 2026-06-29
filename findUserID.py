#!/usr/bin/python3

#Try to lookup the twitter ID based on the handle

#Import libraries
from twython import Twython
import json,sys

#Import twitter api keys needed for the call - from auth.py
from auth import(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret

)

#get the input to routine (the handle)

handle = sys.argv[1]

twitter = Twython(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret
)

#Lookup the input user
a = twitter.lookup_user(screen_name=handle)

#Delete everything in the outputted json stream apart from the user's id - store this as a variable
for i in a:
    userID = i['id_str']


#return the ID    
print(userID)
