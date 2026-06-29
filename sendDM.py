#Used to send notif to user - using twitter dm functionality from API

from twython import Twython
import sys

#Import the API keys
from auth import(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret,
    
) 

#get the input to routine (the twitter ID)

twitterID = sys.argv[1]

twitter = Twython(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret,
    

)


#message = 'Test message!'
#Test to send message - To me - works.
#Send a test message to make sure there are no errors - may need to remind users to make sure that they can recieve dms - Test message and some error handling if the user does not enable messages to be sent to them
#If not able - emails can be sent instead.
#Test message will ensure that their notifications are working.
twitter.send_direct_message(event={'type':'message_create','message_create':{'target':{'recipient_id':twitterID},'message_data':{'text':'The safe crossing time is approaching. Please get back to your vehicle and leave the island safely!'}}})


