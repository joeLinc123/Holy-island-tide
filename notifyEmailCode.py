#!/usr/bin/python3

#Python script to send a notif to a user via email, 2 parameters fed in from the call in the php file
#This is the email to send it to, and the code that was generated


import smtplib, ssl
import sys


email = sys.argv[1]
code = sys.argv[2]
type = sys.argv[3]

if type == 'password':
    time = '10 minutes'
else:
    time = "2 hours"

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

if type == 'password':
    subject = "Password reset code"
    body = (
            "Hello!\n"
            "You have requested a code to reset your password\n"
            "\n"
            "Your code is : " + code + ".\n"
            "You have" + time + "to use this code, then it will expire\n"
            "Thanks, \n"
            "Holy Island crossing")
else:
    subject = "email verification code"
    body = (
            "Hello!\n"
            "Please verify your email\n"
            "\n"
            "Your code is : " + code + ".\n"
            "You have" + time + "to use this code, then it will expire\n"
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
    print("email sent!")