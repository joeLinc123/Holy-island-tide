import smtplib
SERVER = 'localhost'
FROM = 'holyislandcrossing123@gmail.com'

TO = ["jrl0309@icloud.com"]

SUBJECT = "HELLO!"

TEXT = "This message was sent with smtplib"

message = """\
    From: %s
    To: %s
    Subject: %s

    %s
    """ % (FROM, ",".join(TO), SUBJECT, TEXT)


server = smtplib.SMTP('myserver')
server.sendmail(FROM, TO, message)
server.quit()