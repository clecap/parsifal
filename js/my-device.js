/** assume an already trimmed / cropped PDF input (by standalone package)
 */

// call this with: $scale    $CACHE_PATH$hash$inFinal   $CACHE$hash$outFinal

// see   source/tools/murun.c  in mupdf-1.19.0-source   for "documentation" on the used API

if (scriptArgs.length != 3) {print("use:  scale-parameter path");}
else {
  print ("found three script args: scale=" + scriptArgs[0] + " in-path= " + scriptArgs[1] + " out-path= " + scriptArgs[2] );
  
  var scale        = parseFloat(scriptArgs[0]);
  var pdfFileName  = scriptArgs[1] + ".pdf";
  var pngFileName  = scriptArgs[2] + ".png";
  var htmlFileName = scriptArgs[2] + ".html";
  var svgFIleName  = scriptArgs[2] + ".svg";
  
  // for test stub, not for service function 
  var htmlHeader  = "<html><head></head><body style='position:relative;top:0px; left:0px'>";
  var htmlTrailer = "</body></html>";
  var htmlImg     = "<img src='" + pngFileName + "' style='position:absolute;top:0px;left:0px;'></img>";
  
  var doc = new Document(pdfFileName);     // open document
  var page = doc.loadPage(0);              // load first page
  
  print ("page loaded, bounds are: ", page.bound());  
    
  var annos = page.getAnnotations (); 
  var links = page.getLinks();      
   
  print ("Found " + annos.length + " annotations and "  + links.length + " links ");
 
  var linksHtml = "";  // collecting the A tags for the links
  var annosHtml = "";  // collecting the A tags for the hint annotations
  var imgHtml   = "";  // collecting the IMG tags for the hint annotations
  var scripts   = "";  // collecting the SCRIPT tags for the A - IMG connections  
  var arr       = [];  // stores the IMGs we already have rendered so that we only render images once
  for (var i = 0; i < links.length; i++) {
    print ("Link " + i + " bounds=" + links[i].bounds + " uri=" + links[i].uri);
    var left   =  Math.ceil(scale * links[i].bounds[0]);
    var top    =  Math.ceil(scale*links[i].bounds[1]);
    var width  =  Math.ceil (scale* (links[i].bounds[2]-links[i].bounds[0]) ) ;
    var height =  Math.ceil (scale* (links[i].bounds[3]-links[i].bounds[1]) ) ;
    
    // beauty injections: we will add a bit of slack to make the font line nicer
    height += 2;
    width += 4;
    top -=1;
    left -= 1;

    if ( links[i].uri.substring (0,6) != "frame:" ) {
      linksHtml += "<a data-id='a" + links[i].uri + "' style='top:"+top+"px;left:"+left+"px;width:"+width+"px;height:"+height+"px;position:absolute;cursor:pointer;' class='pdf-external-anchor' href='" + (links[i].uri) + "' title='Open external link " + links[i].uri  +"'></a>";
    }
    else {  
      print ("link " + i + " is a hint link");
      var localSrc = links[i].uri.substring (6);
      print ("   localSrc is ", localSrc);
      annosHtml += "<a data-id='a"+localSrc+"' style='top:"+top+"px;left:"+left+"px;width:"+width+"px;height:"+height+"px;cursor:pointer;position:absolute;' class='pdf-external-hint' href='"+localSrc+"' title='Show hint'></a>";
      if (arr.indexOf (localSrc) == -1) {  // if not yet rendered
        imgHtml += "<img id='img"+localSrc+"' src='"+localSrc+"' class='img-hint' style='display:none;position:absolute;'/>\n";
        scripts += "<script>linkHint('"+localSrc+"')</script>\n";
        arr.push (localSrc);  
      }  
      print ("done"); 
    }
  } // for

  print ("links completed");


  for (var i=0; i<annos.length; i++) {   
    print ("type", annos[i].getType(), " flags=", annos[i].getFlags(), " Contents:", annos[i].getContents(), "Rect:", annos[i].getRect() );
    var left   =  Math.ceil(links[i].bounds[0]);
    var top    =  Math.ceil(links[i].bounds[1]);
    var width  =  Math.ceil (links[i].bounds[2]-links[i].bounds[0]) ;
    var height =  Math.ceil (links[i].bounds[3]-links[i].bounds[1]) ;
       
  }
     
   

  var htmlBuf = new Buffer ();
  htmlBuf.write ("<!--  " +scale +  "  -->" + linksHtml + annosHtml + imgHtml + scripts);
  htmlBuf.save (htmlFileName);
  print ("GENERATED: " + htmlFileName);

  var pixmap = page.toPixmap ([scale, 0, 0, scale, 0, 0], DeviceRGB);  // the shift part has an affect on the reported bounds but not on what is displayed in the PNG as it looks like 

  print ("generated png has bounds_", pixmap.bound());
  
  pixmap.saveAsPNG(pngFileName);
  print ("GENERATED: " + pngFileName);
    
}



