#!/usr/local/bin/python3

#Import libraries
import smtplib, ssl
import sys


#Take in params from the calling php - the email and the warning time
email = sys.argv[1]
warning_time = sys.argv[2]



#Establish SMTP connection
port = 587
smtp_server = "smtp.gmail.com"
#set sender and reciever email, as well as the dev password, which allows this to work.
sender_email = "holyislandcrossing123@gmail.com"
reciever_email = email
password = 'gskflfnggeipwgwm'


sent_from = [sender_email]
#Sent to needs to be a list
sent_to=[reciever_email]

#Create the subject and the body of the email
#Message telling the user that the tide is coming in and the user therefore needs to be back in their vehicle.
subject = warning_time + "Minute Warning!! - Important Holy Isalnd crossing Notification"
body = (
        "Hello!\n"
        "The safe crossing time is approaching, please make your way back to your vehicle\n"
        "Thanks, \n"
        "Holy Island crossing")
email_text = """\
From: %s
To: %s
Subject: %s

%s
"""%(sent_from, ",".join(sent_to), subject, body)

context = ssl.create_default_context()
# Send the email


with smtplib.SMTP(smtp_server, port) as server:
    server.ehlo()
    server.starttls(context=context)
    server.ehlo()
    server.login(sender_email, password)
    server.sendmail(sender_email, reciever_email, body)