#!/opt/myenv/bin/python

# Call this with: $scale  $inpath  $outpath  $serverPrefix  (in the caller it should be: $CACHE_PATH$hash$inFinal   $CACHE$hash$outFinal)
# This will produce
#
# Debug info is written to stderr
# Output will receive width and height of the pdf file

import fitz
import sys
import math  


# print ("Found argv ", len(sys.argv))

# bark with error if we have a different number of args than 3. (compare with 4 since the command is counted as well)
if len(sys.argv) != 4 :
  print("use:  scale-parameter in-path out-path server-prefix", file=sys.stderr)
  exit(-1)

# print ("found script args: scale=" + sys.argv[1] + " in-path= " + sys.argv[2] + " out-path= " + sys.argv[3] );

scale        = float(sys.argv[1]);
pdfFileName  = sys.argv[2] + ".pdf";
pngFileName  = sys.argv[3] + ".png";
htmlFileName = sys.argv[3] + ".html";
svgFileName  = sys.argv[3] + ".svg";


#print(fitz.__doc__, file=sys.stderr)
doc = fitz.open(pdfFileName)
page = doc.load_page(0)                                # load page number 0
width, height = page.rect.width, page.rect.height

# print ("page loaded, bounds are: ", page.bound(), file=sys.stderr)


ocgLayers = doc.layer_ui_configs()
ocgNum    = len(ocgLayers)
if ( ocgNum > 0) :
#  print ("Having " + str(ocgNum) + " OCG layers", file=sys.stderr)
  index = 0
  for item in doc.layer_ui_configs():         # for all the ocg layers we know
#    print (item, file=sys.stderr)             # print the layer
    doc.set_layer_ui_config(index, action=2)  # switch number index to status 2=OFF 

    matrix=fitz.Matrix(scale,scale)           # Matrix (2,2) for 2 zoom    mat = fitz.Matrix(zoom_x, zoom_y) 
    pix = page.get_pixmap(matrix=matrix)
    pix.save(pngFileName)                     # store image in a file
    index = index + 1
else:
#  print ("only one content layer", file=sys.stderr)
  matrix=fitz.Matrix(scale,scale)            # Matrix (2,2) for 2 zoom    mat = fitz.Matrix(zoom_x, zoom_y) 
  pix = page.get_pixmap(matrix=matrix)
  pix.save(pngFileName)                      # store image in a file


print(f"{pix.width} {pix.height}")

#print ("\n", file=sys.stderr)
#print ("Annotations", file=sys.stderr)
for annot in page.annots():
  print (annot, file=sys.stderr)


linksHtml = "";  # collecting the A tags for the links
annosHtml = "";  # collecting the A tags for the hint annotations
imgHtml   = "";  # collecting the IMG tags for the hint annotations
scripts   = "";  # collecting the SCRIPT tags for the A - IMG connections  
arr       = [];  # stores the IMGs we already have rendered so that we only render images once

index = 0
for link in page.links():
  print ("IN THE LINK LOOP WITH A LINK", file=sys.stderr)
  print (link, file=sys.stderr)

  left   =  math.ceil(scale * link["from"][0])
  top    =  math.ceil(scale*link["from"][1])
  width  =  math.ceil (scale* (link["from"][2]-link["from"][0]) ) 
  height =  math.ceil (scale* (link["from"][3]-link["from"][1]) ) 
    
  #beauty injections: we will add a bit of slack to make the font line nicer
  height += 2
  width += 4
  top -=1
  left -= 1

  if ( 'uri' in link and len(link["uri"]) >=6 and link["uri"][0:6] != "frame:" ):   # if it is not a frame: link
#    //  THIS BELOW WORKS FOR HTML ALONE
#    //  linksHtml += "<a target='_blank' data-id='a" + links[i].uri + "' style='top:"+top+"px;left:"+left+"px;width:"+width+"px;height:"+height+"px;position:absolute;cursor:pointer;' class='pdf-external-anchor' href='" + (links[i].uri) + "' title='Open external link in new tab: " + links[i].uri  +"'></a>";##
#
#   // TEST
#   // TEST   linksHtml += "<a target='_blank' data-id='a" + links[i].uri + "' style='top:"+top+"px;left:"+left+"px;width:"+width+"px;height:"+height+"px;position:absolute;cursor:pointer;' class='pdf-external-anchor' href='" + (links[i].uri) + "' title='Open external link in new tab: " + links[i].uri  +"'></a>";
    linksHtml = linksHtml + "<a target='_top' data-id='a" + link["uri"] + "' xlink:title='Open external link in new tab: " + link["uri"] + "' xlink:href='"+ link["uri"] + "'><rect x='"+ str(left) +"' y='"+ str(top)+"' width='"+ str(width)+"' height='"+str(height) + "' style='fill:blue;fill-opacity:0.1;stroke-width:0;stroke:blue;' /></a>"

#  if ( 'file' in link ):
#    linksHtml = linksHtml + "<a target='_blank' data-id='a" + link["file"] + "' xlink:title='Open external link in new tab: " + link["file"] + "' xlink:href='"+  serverPrefix + "/index.php?title="  + link["file"] + "'><rect x='"+ str(left) +"' y='"+ str(top)+"' width='"+ str(width)+"' height='"+str(height) + "' style='fill:blue;fill-opacity:0.1;stroke-width:0;stroke:blue;' /></a>"


#    }
#    else {  
#      print ("link " + i + " is a hint link");
#      var localSrc = links[i].uri.substring (6);
#      print ("   localSrc is ", localSrc);
#      annosHtml += "<a data-id='a"+localSrc+"' style='top:"+top+"px;left:"+left+"px;width:"+width+"px;height:"+height+"px;cursor:pointer;position:absolute;' class='pdf-external-hint' href='"+localSrc+"' title='Show hint'></a>";
#      if (arr.indexOf (localSrc) == -1) {  // if not yet rendered
#        imgHtml += "<img id='img"+localSrc+"' src='"+localSrc+"' class='img-hint' style='display:none;position:absolute;'/>\n";
#        scripts += "<script>linkHint('"+localSrc+"')</script>\n";
#        arr.push (localSrc);  
#      }  
#      print ("done"); 
#    }
#  } // for#
#
#  print ("links completed");

#  for (var i=0; i<annos.length; i++) {   
#    print ("type", annos[i].getType(), " flags=", annos[i].getFlags(), " Contents:", annos[i].getContents(), "Rect:", annos[i].getRect() );
#    var left   =  Math.ceil(links[i].bounds[0]);
#    var top    =  Math.ceil(links[i].bounds[1]);
#    var width  =  Math.ceil (links[i].bounds[2]-links[i].bounds[0]) ;
#    var height =  Math.ceil (links[i].bounds[3]-links[i].bounds[1]) ;
#  }
  
  
#  var htmlBuf = new Buffer ();
#  htmlBuf.write ("<!--  " +scale +  "  -->" + linksHtml + annosHtml + imgHtml + scripts);
#  htmlBuf.save (htmlFileName);
#  print ("GENERATED: " + htmlFileName);


with open(htmlFileName, "w") as text_file:
    text_file.write("<!-- %s -->" % scale)
    text_file.write("%s" % linksHtml)
    text_file.write("%s" % annosHtml)
    text_file.write("%s" % imgHtml)
    text_file.write("%s" % scripts)
