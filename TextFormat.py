#Redundant, tried and failed to format the text in python
dates = ['W','Th','F','Sa','Su','M','Tu']
f = open("tidesFormatted.txt", "w")
with open("tidesAll.txt",'r+') as l:
    for current_char in l:
        current_char = str(current_char)

        if current_char in dates or current_char + current_char+1 in dates:

            f.write(str(current_char) + "\n")
        else:
            f.write(str(current_char))



