import PyPDF2
import json


reader = PyPDF2.PdfReader("Holy Island June 22 to 23.pdf")
number_of_pages = reader.getNumPages()
page = reader.getPage(0)
page_content = page.extract_text()
print(page_content)

def get_data(page_content):
    _dict = {}
    page_content_list = page_content.splitlines()
    for line in page_content_list:
        if ':' not in line:
            continue
        key, value = line.split(':')
        _dict[key.strip()] = value.strip()
    return _dict

page_data = get_data(page_content)
json_data = json.dumps(page_data, indent=4)
print(json_data)