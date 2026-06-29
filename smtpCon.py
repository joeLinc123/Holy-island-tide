import smtplib, ssl

smtp_server = "smtp.gmail.com"
port = 587
sender_email = "h.island.crossing@gmail.com"
password = input("Please enter your password: ")

context = ssl.create_default_context()

try:
    server = smtplib.SMTP(smtp_server,port)
    server.ehlo()
    server.starttls(context=context)
    server.ehlo()
    server.login(sender_email, password)

except Exception as e:
    print(e)

finally:
    server.quit()