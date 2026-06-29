# Import Module
import pdftables_api
  
# API KEY VERIFICATION
conversion = pdftables_api.Client('API KEY')
  
# PDf to CSV 
# (Hello.pdf, Hello)
conversion.csv(pdf_file_path, output_file_path)