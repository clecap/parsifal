#!/usr/bin/python

import fitz
import sys
import math  

pdfFileName  = sys.argv[1] + ".pdf";

# print(fitz.__doc__)
doc = fitz.open(pdfFileName)
page = doc.load_page(0)

#print (page.bound())
print(page.rect.width, page.rect.height)
#print(page.mediabox.width, page.mediabox.height)
#print(page.cropbox.width, page.cropbox.height)