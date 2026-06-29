#Import the library
import PyPDF2
#File handling - open output file and input file
file1 = open(r"tidesAll.txt","a")
pdf_file = open('Holy Island June 22 to 23.pdf','rb')
#Create variable to read the pdf file
pdf_reader = PyPDF2.PdfFileReader(pdf_file)
# store the number of pages in the PDF
x = pdf_reader.numPages
#Testing
print(x)
#Iterate through the number of pages
for i in range(x):
    #For each page - get the page, then extract the text and write it in to the output file
    page_object = pdf_reader.getPage(i)

    text=page_object.extract_text()
    file1.writelines(text)
