#Used to send notif to user - using twitter dm functionality from API
from twython import Twython
#Import the API keys
from auth import(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret,
    
) 
#Import userID from the user lookup
from twitterTestlookupuser import(
    userID
)

twitter = Twython(
    consumer_key,
    consumer_secret,
    access_token,
    access_token_secret,
    

)


#userID = input('Please enter a twitter handle: ')
#Find and return user's id
'''
def get_userID(userID):
    twitter.lookup_user(userID)
'''


#message = 'Test message!'
#Test to send message - To me - works.
#Send a test message to make sure there are no errors - may need to remind users to make sure that they can recieve dms - Test message and some error handling if the user does not enable messages to be sent to them
#If not able - emails can be sent instead.
#Test message will ensure that their notifications are working.
twitter.send_direct_message(event={'type':'message_create','message_create':{'target':{'recipient_id':userID},'message_data':{'text':'10 mins to get out of Holy Island!'}}})
#twitter.update_status(status="Sunderland AFC is the biggest team in the north East! Am i right {}".format(userID))
#print("Tweeted: %s" %message)
'''
except:
    print("There was an error!")
'''