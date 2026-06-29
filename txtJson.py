import json

filename = 'tidesAll.txt'

json_dict = {}

fields = ['date','occupation','wage']



def create_json(fields, json_dict,filename):
    z = 1
    with open(filename) as fh:

        for line in fh:

            description = list( line.strip().split(None, 3))

            print(description)


            

            id = 'emp'+str(z)

            i = 0

            json_dict2 = {}
            while i<len(fields):

                json_dict2[fields[i]] = description[i]
                i+=1

            json_dict[id] = json_dict2
            z+=1
    
    return json_dict

create_json(fields, json_dict, filename)


json_out = open("JSON_data","w")
json.dump(json_dict, json_out, indent = 4, sort_keys = False)
json_out.close()
