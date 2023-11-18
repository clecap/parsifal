<?php

require_once (__DIR__."/../config/config.php");
require_once ("polyfill.php");                       // include some PHP polyfill stuff
require_once ("Decorator.php");



class TeXProcessor {

/** purges the parser cache of a page with given title */
private static function purgeByTitle ($titleText) {
  $title   = Title::newFromText($titleText);
  $article = new Article($parentTitle);
  $article->mTitle->invalidateCache();
}

/** Generate Latex and Pdflatex precompiled versions of the file $name and place them into the respective format directories. */
public static function precompile ($name) {
  $VERBOSE = false;
  $TEMPLATE_PATH = TEMPLATE_PATH; $LATEX_FORMAT_PATH = LATEX_FORMAT_PATH; $PDFLATEX_FORMAT_PATH = PDFLATEX_FORMAT_PATH;
  
  self::ensureEnvironment();
  if ($VERBOSE) {self::debugLog ("TeXProcessor::precompile: Tex2Pdf sees the following environment via getenv(): \n".print_r (getenv(), true) );}
  if ($VERBOSE) {
    self::debugLog ("\n TeXProcessor::precompile shel exec dumping environment sees: \n"); 
    exec ( "env >> ".LOG_PATH );
    self::debugLog ("\n --- DONE --- \n");  
  }

  self::cleanUpAll();  // clean up ALL existing /tmp files since all pages have to be re-done
  
  // clear the format files to ensure that we do not continue to use an old format when a compilation fails and produces no new format file
  if (file_exists ("$LATEX_FORMAT_PATH/$name.fmt"))    { unlink ("$LATEX_FORMAT_PATH/$name.fmt");      }
  if (file_exists ("$LATEX_FORMAT_PATH/$name.fls"))    { unlink ("$LATEX_FORMAT_PATH/$name.fls");      }
  if (file_exists ("$LATEX_FORMAT_PATH/$name.log"))    { unlink ("$LATEX_FORMAT_PATH/$name.log");      }
  if (file_exists ("$PDFLATEX_FORMAT_PATH/$name.fmt")) { unlink ("$PDFLATEX_FORMAT_PATH/$name.fmt");   }
  if (file_exists ("$PDFLATEX_FORMAT_PATH/$name.fls")) { unlink ("$PDFLATEX_FORMAT_PATH/$name.fls");   }
  if (file_exists ("$PDFLATEX_FORMAT_PATH/$name.log")) { unlink ("$PDFLATEX_FORMAT_PATH/$name.log");   }
  
  $cmd1 = "latex  --interaction=nonstopmode  -file-line-error-style  -ini -recorder -output-directory=$LATEX_FORMAT_PATH \"&latex $LATEX_FORMAT_PATH/$name.tex\dump\" "; 
  if ($VERBOSE) { self::debugLog("TeXProcessor::precompile: will now execute the following latex precompile command: \n  ".$cmd1."\n"); }  
  $output1 = null; $retVal1 = null;  
  $retVal1 = TeXProcessor::executor ($cmd1, $output1, $error1, true, $duration1);

  if ($VERBOSE) { self::debugLog("TeXProcessor::precompile: latex command returned: $retVal1 and output: " . print_r ($output1, true)); }
  
  $cmd2 = "pdflatex  --interaction=nonstopmode  -file-line-error-style  -ini -recorder -output-directory=$PDFLATEX_FORMAT_PATH \"&pdflatex $PDFLATEX_FORMAT_PATH/$name.tex\dump\" ";    
  if ($VERBOSE) { self::debugLog("TeXProcessor::precompile: will now execute pdflatex precompile command ".$cmd2."\n"); }
  $output2 = null; $retVal2 = null; 
  $retVal1 = TeXProcessor::executor ($cmd2, $output2, $error2, true, $duration2);
  if ($VERBOSE) {self::debugLog("TeXProcessor::precompile: pdflatex command returned: $retVal2 and output: " . print_r ($output2, true)); } 
  if ($retVal1 != 0 || $retVal2 != 0) {
    return "\nCommand1 was: $cmd1\nRetVal1 was $retVal1\nOutput1 was " . print_r($output1, true) . "\n" . print_r ($error1, true) . "\n\n". 
           "\nCommand2 was: $cmd2\nRetVal1 was $retVal2\nOutput1 was " . print_r($output2, true);
  }
  else {return false;}
}


#region timeTest:  testing function during development to see how long the individual production chains run
public static function timeTest ($in, $tag) {
  self::debugLog ("------------------- STARTING TIME TEST --------------------------\n");
  
  $hash = self::generateTex ($in, $tag, "");     // generates $hash.tex
  self::generateTex ($in, $tag, "pc_latex");     // generates $hash_pc_latex.tex
  self::generateTex ($in, $tag, "pc_pdflatex");  // generates $hash_pc_pdflatex.tex

//  self::Tex2DviLatex  ($hash, "");
//  self::Tex2DviLatex  ($hash, "_pc_latex");
  
  self::Tex2Pdf       ($hash, "_pc_pdflatex", "timetest");
  
  // self::Tex2DviLatex  ($hash, "_pc_pdflatex");    // NON working combination 
//  self::Tex2DviPdflatex  ($hash, "");
//  self::Tex2DviPdflatex  ($hash, "_pc_latex");
//  self::Tex2DviPdflatex  ($hash, "_pc_pdflatex");    
 // self::generatePdfBboxGS ($hash);  
//  self::generateNodePngHtml ($hash, self::SCALE (1200, 15));
//  self::Dvi2Png   ($hash, self::DPI(800, 15), 1.5);     
//  self::Pdf2PngMT ($hash, self::DPI(800, 15), "_pc_pdflatex", "_mt" );     
    
  self::Pdf2PngHtmlMT ($hash, self::SCALE(800, 15), "_pc_pdflatex", "_pc_pdflatex" ); 
  
  //self::Pdf2SvgMT ($hash, self::DPI(800, 15) );       
  //self::Pdf2PngGS ($hash, self::DPI(800, 15) );     
  self::debugLog ("------------------- COMPLETED TIME TEST --------------------------\n");
}
#endregion



/** generate stuff to be placed into the preamble but at the end of the preamble
 *  this is of particular importance for those parts of the preamble which cannot be precompiled
 */
private static function generateEndPreambleStuff ($ar, $tag) {
  global $wgServer, $wgScriptPath;
  $stuff = "";

  // array key   "sans"  turns the default font into a sans serif font
  if ( array_key_exists ( "sans", $ar ) ) { $stuff = $stuff."\\renewcommand{\\familydefault}{\\sfdefault}"; }

  // array keys  de, nde, babel   and  default: english
  if ( array_key_exists ( "de", $ar ) )          { $stuff = $stuff."\\usepackage[shorthands=off,german]{babel}";   }
  else if ( array_key_exists ( "nde", $ar ) )    { $stuff = $stuff."\\usepackage[shorthands=off,ngerman]{babel}";  }
  else if ( array_key_exists ( "babel", $ar ) )  { $stuff = $stuff."\\usepackage[shorthands=off,".$ar["babel"]."]{babel}";  }
  else                                           { $stuff = $stuff."\\usepackage[shorthands=off,english]{babel}";  }

  $light = array ("manny", "rrt", "perldoc", "borland", "colorful", "murphy", "vs", "trac", "tango", "autumn", "bw", "emacs", "pastie", "friendly");
  $dark  = array ( "fruity", "vim", "native", "monokai");

  // array key   "minted"  if present: adds package minted and uses style emacs
  //                       if present and has a value: uses that value as style for minted, provided the style is known
  if ( array_key_exists ("minted", $ar) ) {                                                       // if we have a minted attribute, include minted stuff
    if ( in_array ($ar["minted"], $light) ) { $style=$ar["minted"]; } else { $style = "emacs";}   // check if style is known. If it is not, use emacs as default
    $stuff = $stuff . "\\usepackage[outputdir=".CACHE_PATH.",newfloat=true,cache]{minted}\\usemintedstyle{" .$style. "}\\initializeMinted"; 
  }


  // array key    "pa" if present and has contents
  //                   if contents starts with [[  and ends with ]] then use the string in between as reference to a Mediawiki parsifal template file
  $addPreamble = "";
  if ( array_key_exists ("pa", $ar) ) {
    if ( str_starts_with ( $ar["pa"], "[[") &&  str_ends_with ( $ar["pa"], "]]" ) ) {
      $name = substr  ($ar["pa"], 2, -2 );
      $configPage = "ParsifalTemplate/$name";                                                              // name of the MediaWiki:Sidebar$name configuration page of this portlet
      $title      = Title::newFromText( $configPage, NS_MEDIAWIKI );                              // build title object for MediaWiki:SidebarTree
      if ($title == null) { throw new Exception ("no template file $name found for Parsifal");}                                                         // signal the caller that we did not find a configuration page for this portlet
      $wikipage   = new WikiPage ($title);                                                        // get the WikiPage for that title
      if ($wikipage == null) { throw new Exception ("no wikipage $name found for Parsifal");}                                                      // signal the caller that we did not get a WikiPage
      $contentObject = $wikipage->getContent();                                                   // and obtain the content object for that
      if (!$contentObject) { throw new Exception ("no contentobject $name found for Parsifal"); }
      $contentText = ContentHandler::getContentText( $contentObject );    
      $addPreamble = extractPreContents ($contentText);
    }   
  else { $addPreamble = $ar["pa"];}
  }

  // add some further stuff into the preamble
  $optional = <<<EOD
\\usepackage{environ}%         Needed for some additional definitions
\\usepackage{ocg-p}%

\\newcounter{optionals}
\\NewEnviron{opt}[1]{
  \\stepcounter{optionals}
  \\begin{ocg}{#1}{oxc\\theoptionals}{0}%  Argument is name.  Body is content. It is initially not visible.
  \\BODY%
  \\end{ocg}%
}
\\NewEnviron{OPT}[1]{%    capitalized: initially visible
  \\stepcounter{optionals}
  \\begin{ocg}{#1}{oxc\\theoptionals}{1}%  Argument is name.  Body is content. It is initially visible.
  \\BODY%
  \\end{ocg}%
}
EOD;

  

//  $urlStuff = "\\newcommand{\dref}[1]{ \\StrSubstitute{#1}{ }{_}[\\temp]\\href{".   $wgServer.$wgScriptPath . "/index.php?title="."\\temp}{#1}}";

 $urlStuff = "\\newcommand{\dref}[1]{ \\StrSubstitute{#1}{ }{_}[\\temp]\\href{".   $wgServer.$wgScriptPath . "/index.php/"."\\temp}{#1}}";


  return $stuff . $urlStuff. $optional . $addPreamble;
}

// minipage is used to hold the page width stable. without it we also get some indentation artifacts with enumitem.

private static function generateBeforeContentStuff ($ar, $tag) { 
  $stuff    = "";
  $variants = "";
  
  // implement size related attributes
  $size="15cm";
  if ( array_key_exists ( "wide", $ar ) ) { $size="25cm";}
  if ( array_key_exists ( "slim", $ar ) ) { $size="5cm";}
  if ( array_key_exists ( "width", $ar) ) { $size=$ar["width"];}

  $margin="0cm";
  if ( array_key_exists ( "margin", $ar) ) { $margin=$ar["margin"];}

  $config = "\\standaloneconfig{varwidth=".$size.",margin=".$margin."}"; // need to override the standalone documentclass option of the template

  // implement variant related properties
  if ( array_key_exists ( "number-of-instance", $ar ) ) { 
    $variants = "\\gdef\\numberOfInstance{" . $ar["number-of-instance"] . "}";
  }




  # CAVE: confusion between tex { } and php { } should be avoided !
  switch ($tag) {
    case "amsmath":   $stuff = $config."\\begin{document}"."\\begin{minipage}{".$size."}\\relax ".MAGIC_LINE."\\end{minipage}\\end{document}"; break;
    case "tex":       $stuff = MAGIC_LINE; break;
    case "beamer":    $stuff = "\\begin{document} ".MAGIC_LINE."\\end{document}"; break;
  }

  $stuff = $variants . $stuff;

  return $stuff;
}




public static function lazyRender ($in, $ar, $tag, $parser, $frame) {
  global $wgServer, $wgScriptPath, $wgOut; 
  global $wgTopDante;
  global $wgAllowVerbose; $VERBOSE = false && $wgAllowVerbose;

  $startTime = microtime(true);  
  if ($VERBOSE) {self::debugLog ("lazyRender called at $startTime \n");}
  if ($VERBOSE) {self::debugLog ("Attributes: " . print_r ($ar, true)."\n");}

  // we need to know the title of the page in order to do a proper purge of the page in case an image is not found but should be there 
  $titleText = ""; 
  $title = $parser->getTitle();
  if ($title) {$titleText = $title->getText();}

  $CACHE_PATH = CACHE_PATH;

  $timestamp = strftime ("%d.%m.%Y at %H:%M:%S", time() );         // want a timestamp in the img tag on when the page was translated for debugging purposes
  $hrTimestamp = hrtime(true);
  if ($VERBOSE) {$startTime = microtime (true); self::debugLog ("lazyRender called at: $timestamp nanos=$hrTimestamp $hrTimestamp for pageTitle " . $parser->getTitle()."  Page starts with: ". trim(substr(trim($in), 0, 20)) . "\n");}

  if (!is_object ($wgTopDante)) {throw new Exception ("It looks like you did not set variable \$wgTopDante. Please consult installation instructions"); }

try {  // GLOBAL EXCEPTION PROTECTED AREA

    if (property_exists ($wgTopDante, "hookInvokes") ) { $wgTopDante->hookInvokes++; } else { $wgTopDante->hookInvokes = 0; ;}
    if ($VERBOSE) {self::debugLog ("----------------------------------------lazyRender sees hookInvokes=".$wgTopDante->hookInvokes."\n") ;}

  self::ensureEnvironment ();

  $texSource=null;  // set this to  "STUFF" if we want it to be filled in
  // TODO
  $hash       = self::generateTex ($in, $tag, "pc_pdflatex", $ar, $texSource);      // generate $hash_pc_pdflatex.tex and obtain hash of raw LaTeX source located in Mediawiki
  
  // can be used to trace from where we have been called
  // $tag = "none";  if ( isset ($parser->danteTag) ) {$tag = $parser->danteTag;}
  // self::debugLog ("lazy $tag sees $hash\n");

  // accumulate in the parser connected output object of that page an array with all the hash values used on this page; do so for cleaning up hashs which became stale // TODO
  $usedHashs = unserialize ( $parser->getOutput()->getPageProperty( 'ParsifalHashsUsed' ) );
  if ( strcmp (gettype ($usedHashs), "array") != 0 ) {$usedHashs = array();}  // if not found, initialize in empty array()
  array_push ($usedHashs, $hash); // TODO: ??????? what for 
  $parser->getOutput()->setPageProperty( 'ParsifalHashsUsed', serialize ($usedHashs));  // TODO: what do we use this for - clearing files ?? - do we still use it for this purpose ??
  
  // set some paths
  $texPath        = $CACHE_PATH.$hash."_pc_pdflatex.tex"; 
  $finalImgUrl    = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex_final.png"; 

  $annotationPath = $CACHE_PATH.$hash."_pc_pdflatex_final_3.html";                         // the local php file path under which we should find the annotations in form of a (partial) html file  // TODO: hardcoded resolution is bad
  $finalImgPath   = $CACHE_PATH.$hash."_pc_pdflatex_final_3.png";   // TODO: cave hardcoded resolution is bad
  $errorPath      = "$wgScriptPath/extensions/Parsifal/html/texLog.html?"."$wgServer$wgScriptPath".CACHE_URL.$hash."_pc_pdflatex";

  $softError = "";

  /** LATEX to PDF production step **/
  if ($VERBOSE) {self::debugLog ("lazyRender will now Tex2PDF $hash... ") ;}
  if ( !file_exists ($CACHE_PATH . $hash . "_pc_pdflatex.pdf" ) ) {  // if the PDF file does not exist, make it and pick up error status from function
    if ($VERBOSE) {self::debugLog ( "did not find file " . $CACHE_PATH . $hash . "_pc_pdflatex.pdf, starting processor... " . $hash );}


   $softError = self::Tex2Pdf ($hash, "_pc_pdflatex", "lazyrender");  

//    $softError = self::Tex2PdfPiped ($texSource, $hash, "_pc_pdflatex");    // TODO: maybe a piped version is faster ???
      MWDebug::log ( "Softerror Case 1 is: $softError \n" );
  }
  else {                                                             // if the PDF file does exist, pick up the earlier softError status from the marker file
    if ($VERBOSE) {self::debugLog ( "found file on disc\n" );}
    $mrkFileName = $CACHE_PATH . $hash. "_pc_pdflatex.mrk";
    $fileSize = filesize ( $mrkFileName );
    if ( $fileSize === false ) {
      throw new ErrorException ("lazyRender: Could not find error marker file " . $CACHE_PATH . $hash. "_pc_pdflatex.mrk");  

    }
    else if ( $fileSize > 0 ) { $softError = file_get_contents ($mrkFileName);  MWDebug::log ( "Softerror Case 2 is: $softError \n" );}
    else /* fileSize == 0 */  { $softError = "";                                MWDebug::log ( "Error Marker file is empty \n" );  }
  }

  // NOW we should have a PDF file - if not it could still be a transient error from latex which in this particular situation was unable to produce a pdf although it could run  TODO: do an error handling for this scenario !!

  // obtain size
  $width=200; $height=200;
//  self::getPdfSize ($hash, $width, $height );

  $annotationCode = "";

  $mustDoImage = $mustDoAnnotations = false;
  if ( !file_exists ($finalImgPath)   ) { $mustDoImage       = true;  if ($VERBOSE) {self::debugLog (" $finalImgPath is missing, need to call processor...");} }
  if ( !file_exists ($annotationPath) ) { $mustDoAnnotations = true;  if ($VERBOSE) {self::debugLog (" $annotationPath is missing, need to the processor...");} }

//  if ( $mustDoImage || $mustDoAnnotations ) { 
  if ( !file_exists ($finalImgPath) ) {
    if ($VERBOSE) {self::debugLog (" one of the files detected missing, calling the processor...");}

  // TODO: ALLOW to set some basic scale somewhere in or by or for the ParsifalTemplate !!!! itself - so we do nto need to add it in the (in every) tag.

  $baseScale = 3;
  if ( array_key_exists ("scale",$ar)) {  
    $tagScale = floatval($ar["scale"]);
      if ( is_float ($tagScale) ) { $baseScale = 15/$tagScale;   }
  }
 //    echo "SCALE: " . $baseScale;

  $scaler = self::SCALE(BASIC_SIZE, $baseScale);
 // echo "SCALER: ". $scaler;

  $pdfscale = $scaler;
  if ( array_key_exists ("pdfscale",$ar)) {  $pdfscale = floatval($ar["pdfscale"]); }

  self::debugLog ("Pdf2PngHtmlMT for $hash starting\n");

  self::Pdf2PngHtmlMT ($hash, $pdfscale, "_pc_pdflatex", "_pc_pdflatex_final_3", $duration );  // 15 
//  $annotationCode = self::Pdf2PngHtmlMT_RET ($hash, $pdfscale, "_pc_pdflatex", "_pc_pdflatex_final_3", $duration );

  self::debugLog ("Pdf2PngHtmlMT for $hash completed\n");
  }
  else { if ($VERBOSE) {self::debugLog ("both files found on disc, no need to call processor\n");} }
   
  // We need a width and height in the img tag to assist the browser to a more smooth and flicker-less reflow.
  // The width MUST be equal to the width of the image (or else the browser must rescale the image, which BLURS the image and takes TIME)

// We need a width and height in the img tag to assist the browser to a more smooth and flicker-less reflow.
  // The width MUST be equal to the width of the image (or else the browser must rescale the image, which BLURS the image and takes TIME)
  if (file_exists ( $finalImgPath ) ) {
     $hasError = "";
     clearstatcache ( true, $finalImgPath );  // looks like htis is necessary to ensure getimagesize gets the correct answer all the time
    $ims = @getimagesize ( $finalImgPath ); 
    if ($ims) {$width = $ims[0]; $height = $ims[1];} else {
      $imgFileSize = filesize ( $finalImgPath );
      throw new ErrorException ("Looks like $finalImgPath is not yet ready. File size reports it as $imgFileSize ");}   } 
  else {  
      $width=200; $height=200; $hasError = "data-error='missing-image'";  // signal to JS runtime that we know the image is in error
      // return "Currently we have no image for display. It is possible that the LaTeX source did not produce any output. Missing file is $finalImgPath"; 
  }


  $scaling = 25;

   // self::debugLog ("lazy will now make img tag for $hash\n");

  // onload:    delay showing the image until it is completely loaded (prevents user from seeing half of an image during the load process)
  // onerror:   kickoff generation of image should it be missing (reason could be: file was (incorrectly) deleted on the server)
  $naming = ( array_key_exists ("n", $ar) ? "data-name='".$ar["n"]."' " : "");             // prepare a data-name attribute for the image
  $title = $parser->getTitle ();                                                           // get title of current page (also need this below !   // CAVE:  WILL  need different call,  getPage()   from 1.37 on !!!!!!!!!!!!!!!!!!!!

/////////////// TODO: ??????????????? We must check / ensure that this name has not yet been used already on this page with this name - and similarly in the entire system !!!!!!!!!
/// this should mirgate into DanteSnippets
  if (array_key_exists ("n", $ar)) { // if tag has a name then produce a further copy of the page
//    TeXProcessor::$makeFileStack[$title."/".$ar["n"]] = "<$tag>\n$in\n</$tag>"; 
    MWDebug::log ( "Found a name : ".$ar['n']."\n" );
   //$snip =  new Snippets ( ) ;  
  }
 
  // image tag style
  $style = "style=\"";

  $markingClass = "";
  if ( array_key_exists ("number-of-instance", $ar) ) { $style .= "margin:20px;"; 
    $markingClass = "instance_".$ar["number-of-instance"];
  }

  if ( array_key_exists ("b", $ar) )  { $style .= "border:1px solid gold;";            }     // add a border
  if ( array_key_exists ("br", $ar) ) { $style .= "border-radius:5px;";                }     // add a border radius
  if ( array_key_exists ("bs", $ar) ) { $style .= "box-shadow: 10px 10px lightgrey;";  }     // add a box shadow
  if ( array_key_exists ("style", $ar) )  { $style .= $ar["style"];            }             // add custom style
  $style .= "width:100%;";
  $style .= "display:none;";
  $style .= "\" ";

  $titleInfo = "title=\"I am a title information\"";

  $finalImgUrl3   = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex_final_3.png"; 
  $finalImgUrl    = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex_final.png"; 

  $cache_url = CACHE_URL;

  $dataTitle = "data-title=\"".$titleText."\"";   // TODO: could be susceptible to html injection - fix
  $dataHash  = "data-hash=\"".$hash."\"";


$hasError="";  // what for ?????

  $imgTag      = "<img $naming id=\"$hash\" $dataTitle $dataHash $hasError  data-timestamp='$timestamp'  $style  class='texImage' alt='Image is being processed, please wait, will fault it in automatically as soon as it is ready'></img>";
  $handlerTag  = "<script>PRT.srcDebug(\"$hash\"); PRT.init(\"$hash\", \"$wgServer\", \"$wgScriptPath\", \"$cache_url\"  ); </script>"; 

  $imgResult = $imgTag . $handlerTag;  // the image tag which will be used

  $annotations  = (file_exists ($annotationPath) ? file_get_contents ($annotationPath) :  null );  // get annotations, if no file present, use null

  $core = new Decorator ( $imgResult, $width, $height, $markingClass);
  $core->wrap ( $annotations, $softError, $errorPath, $titleInfo);      // wrap with annotations and error information   
  $core->collapsible ( $ar );                               // decorate with collapsibles
  $ret = $core->getHTML ();                                 // generate HTML which includes the decorations

  if (true || $VERBOSE) {$endTime = microtime (true); $totalDuration = $endTime - $startTime;  self::debugLog ("lazyRender: COMPLETED. TOTAL RUNTIME OF lazyRender =$totalDuration ------------------------------------------------- \n\n\n");}

} catch (\Exception $e) { $msg = $ret = $e->getMessage();  $ret = "<b>$msg</b><br>".$e->getTraceAsString();}  // in case of exception, build a suitable error element 

// TODO: depending on the rendering mode we do not have to wait until we have the PNG and HTML formats available !!!

  return $ret;                                            // for THIS (img) the calculated width and height from above from PNG are correct
}
























// given a $hash, returns an html object tag
// disadvantage: this produces an entire player frame, which we do not want: DO NOT USE
public static function objectPdf ($hash, $width, $height) {
  global $wgServer, $wgScriptPath;
  $style = "";
  $url   = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex.pdf";
  $html = "<object width='".$width."' height='".$height."' style='max-width: 100%;' data='".$url."' type='application/pdf'></object>";
  return $html;
}

// given a $hash, returns html code with a rendering canvas
// disadvantage: looks like pdfjslib is not properly reentrant and the parallel calls this may caus for several canvases could lead to issues
// they show up when we have several canvases on the same html pag
public static function canvasPdf ($hash, $width, $height) {
  global $wgServer, $wgScriptPath;
  $url = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex.pdf";
 // $canvas = "<canvas width='".$width."' height='".$height."' style='max-width: 100%; border:2px solid red;' data='".$url."'  id='canvas-".$hash."'  ></canvase>";
 $canvas = "<canvas  style='max-width: 100%; border:2px solid red;' data='".$url."'  id='canvas-".$hash."'  ></canvas>";

  $js     = "<script>PRT.renderPDF ('".$url."','".$hash."');</script>";
  $html = $canvas . $js;
  return $html;
}

// given a $hash, return html code with an iframe rendering this inside of the iframe alone
public static function iframePdf ($hash, $width, $height, $scale, $titleInfo) {
  global $wgServer, $wgScriptPath;
  $basis  = $wgServer.$wgScriptPath."/extensions/Parsifal/html/pdfIframe.html";    // path to the html page generating canvas 
  $url    = $wgServer.$wgScriptPath.CACHE_URL.$hash."_pc_pdflatex.pdf";
  $urlSearch = "url=".urlencode ($url);
  $urlScale  = "scale=".urlencode ($scale);
  $urlInfo   = "info=true";
  $urlHash   = "hash=".urlencode ($hash);
  $iframeUrl = $basis."?".$urlSearch."&".$urlScale."&".$urlInfo."&".$urlHash;

//  $style = "max-width:100%;" . "width:".$width."px; height:".$height."px; border:1px solid green;";

  $width += 172;  // 62

  $style = "max-width:100%;" . "width:".$width."px; height:".$height."; border:1px solid red; overflow:hidden; ";

//  $style = "max-width:100%; width:100%; border:1px solid green;";

  $infoLine = "<div>TexProcessor.php: Infoline: iframe is: width=$width  height=$height</div>";

  $html = $infoLine."<iframe   scrolling='no'     style='".$style."' src='".$iframeUrl."'  id='iframe-".$hash."' title='".$hash."' ></iframe>";
  return $html;
}

// render by producing a call to Parsifal Runtime.
// advantage: client can decide, how to render a page
public static function jsRender ($hash, $width, $height, $scale, $titleInfo) {
  global $wgServer, $wgScriptPath;
  return "<script> PRT.jsRender(\"$hash\", $width, \"$height\", $scale, \"$titleInfo\", \"$wgServer\", \"$wgScriptPath\", \""  .CACHE_URL. "\");</script>";
}



  // NOTE: TeXProcessor::renderPreviewPNG and _base64 and called functions can be called from the web and from mediawiki and hence must not depend on any mediawiki global variables - all configuration done in config.php


/* SCALING CALCULATION: Given the number of pixels we have available in the presentation IMG, what is the required DPI value to be used in divpng?
   
   We get 591 pixel width for 100 dpi for a document of Latex width 15cm
   15cm = 5,90551 inches  in resolution 100 dpi we get 590,551 dots and with rounding 591 as width 
     
   Pixels = dpi * LatexWidthInCm * cmToInches  
   dpi = availablePixelWidth / (LatexWidthInCm * cmToInches) = 2.54 * availablePixelWidth / LatexWidthInCm

   We have $pixels many pixels available in the HTML area of our image.
   The text document is 
*/
public static function DPI ($pixels, $textWidthCm) { return floor (2.54 * $pixels / $textWidthCm); }


/* Scaling calculation: Given the number of pixels we have available in the presentation IMG, what is the required scale value to be used in pdf.js rendering?
    
  We get 1079 pixel width when we compile a 15cm width Latex document with a scale of 2.54.
  We get 424  pixel width when we compile a 15cm width Latex document with a scale of 1.00
  
  In scale = 1.00 we get: Per cm Latex: 28 pixel.  Per inch Latex: 72 pixel 
  
  LatexWidthInCm [cm] of  latex are  LatexWidthInCm * cmToInches [inches] are LatexWidthInCm * cmToInches * 72 [pixel] = LatexWidthInCm *72 / 2.54  [pixels]

  scale = 2.54 * pixels / ( textWidthCm * 72 )
*/
public static function SCALE ($pixels, $textWidthCm) {return (2.54 * $pixels) / (1.0 * $textWidthCm * 72);}



/* ensures that PHP has the right concept required for the TeX processing environment */
public static function ensureEnvironment () {
  $VERBOSE  = false;
  $ALREADY  = "already";

  // $start = hrtime (true);

  $parsifalenvironment = getenv('PARSIFALENVIRONMENT');
  if ( strcmp ($parsifalenvironment, $ALREADY) == 0 ) {
    if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment: environment has already been setup - exiting \n");}
    return;
  }

  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment before the environment patching sees the following environment: \n".print_r (getenv(), true) );}  
  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment patches TEXDIR=".TEXDIR."\n" );}  
  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment patches PATH=".PATH."\n" );}  

  // set the environment variables as specified in the TeXLive installer
  $flag = true;
  $flag = $flag and putenv ("PARSIFALENVIRONMENT=already");
  $flag = $flag and putenv ("TEXDIR=".TEXDIR); 
  $flag = $flag and putenv ("TEXMFLOCAL=".TEXMFLOCAL);
  $flag = $flag and putenv ("TEXMFSYSVAR=".TEXMFSYSVAR); 
  $flag = $flag and putenv ("TEXMFSYSCONFIG=".TEXMFSYSCONFIG); 
  $flag = $flag and putenv ("TEXMFVAR=".TEXMFVAR);
  $flag = $flag and putenv ("TEXMFCONFIG=".TEXMFCONFIG);    
  $flag = $flag and putenv ("TEXMFHOME=".TEXMFHOME);      

  $flag = $flag and putenv ("max_print_line=1000");      
  $flag = $flag and putenv ("error_line=254");      
  $flag = $flag and putenv ("half_error_line=238");      

  // need PATH to tex binaries and to standard OS binaries such as sed and others for font generation scripts
  $flag = $flag and putenv ("PATH=".PATH);
  $flag = $flag and putenv ("HOME=".HOME);
  
  // want to access local style and packges files from a local directory
  $flag = $flag and putenv ("TEXINPUTS=".TEXINPUTS);

  if ($flag === false) {throw new Exception ("Parsifal was unable to putenv environment. Must check if putenv is enabled in php.ini or similar!");}

  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment: putting PATH to env returned: ".print_r ( putenv("PATH=".PATH) . "\n", true) );}  
  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment: getting PATH from env returned: ".print_r ( getenv("PATH") . "\n", true) );}  
  if ($VERBOSE) {self::debugLog ("TeXProcessor::ensureEnvironment: after environment patching sees the following environment: \n".print_r (getenv(), true) );}  

  //$duration = hrtime(true)-$start; self::debugLog ("TeXProcessor::ensureEnvironment duration: $duration \n");  // negligent. some 20 micro seconds
}


public static function ensureCacheDirectory () {
  $CACHE_PATH = CACHE_PATH;
  // TeXProcessor::debugLog ("ensuring existence of cache directory: $CACHE_PATH \n");
  if ( !file_exists ($CACHE_PATH) ) {
    TeXProcessor::debugLog( "TeXProcessor::ensureCacheDirectory: detected a missing cache directory; trying to construct: $CACHE_PATH \n");     
    $retVal = mkdir ($CACHE_PATH, 0755);  
    TeXProcessor::debugLog( "  mkdir returned $retVal \n");
  }
}



// called from endpoints/tex-preview.php  // TODO: IS THIS STILL IN USE or can we deprecate this ????
// TODO: if we still need it: NOTE: the array values are actually not provided to generateTex below !!!!!!!
public static function texPreviewEndpoint () {
  $VERBOSE    = true; 
  $CACHE_PATH = CACHE_PATH;
  
  TeXProcessor::ensureEnvironment ();  
  umask (0077);                                                 // preview files should be generated at 600 permission
  
  $body = file_get_contents("php://input");                     // get the input; here: the raw body from the request
  $body = base64_decode ($body);                                // in an earlier version we used, unsuccessfully, some conversion, as in:   $body = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $body); 
  
  // obtain and sanitize further parameter values from the http header
  $tag                  = $_SERVER['HTTP_X_PARSIFAL_TAG'];
  $paraText             = $_SERVER['HTTP_X_PARSIFAL_PARA'];   
  $availablePixelWidth  = $_SERVER['HTTP_X_PARSIFAL_AVAILABLE_PIXEL_WIDTH'];     if (! isset ($availablePixelWidth)) { $availablePixelWidth = 600; }
 
// $widthLatexCm         =  TAG2WIDTHinCM[$tag];                                  if (! isset ($widthLatexCm))        { $widthLatexCm = 15; }  
// TAG2WIDTH has been deprecated  

  if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: sees tag: " . $tag . " widthLatexCm: ". $widthLatexCm . "  availablePixelWidth: ". $availablePixelWidth . " \n");}
  
  try {
    $hash    = "NOT YET SET, CRASHED TOO EARLY";                                      // we want a sane value in case self::generateTex crashes for some reason
    $hash    = self::generateTex ($body, $tag, "pc_pdflatex");                        // prepare for a precompiled pdflatex run
    $retval  = self::Tex2Pdf ($hash, "_pc_pdflatex", "endpoint");                     // do a precompiled pdflatex run
    
    $para = json_decode ( $paraText, true);                  // decode parameter object (which we might get from the endpoint)
    $dpi =  self::DPI ($availablePixelWidth, $widthLatexCm); if ( array_key_exists ("dpi", $para) )   { $dpi = $para["dpi"]; if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: finds dpi override = ".$dpi. "\n");} }  // DPI parameter
    $gamma = 1.5; if ( array_key_exists ("gamma", $para) ) { $gamma = $para["gamma"];  if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: finds gamma override = ".$gamma. "\n");} }                               // GAMMA parameter
        
    $name =  $CACHE_PATH . $hash."_pc_pdflatex.png";
    $scale = self::SCALE($availablePixelWidth, $widthLatexCm); 
    self::Pdf2PngHtmlMT ($hash, $scale, "_pc_pdflatex", "_pc_pdflatex", $duration );            // png must be redone since scale depends on width of preview area
     
//// TODO: currently it looks like we do not send the annotations and html portion for the preview
//     this might be ok but why do we then compute them??
//     and: when load is low we could also kick off generation of the final version

    // send the result to the client
    if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: will now send $name to client, dpi=$dpi, gamma=$gamma \n");}
    if (filesize($name) == 0) { throw new Exception ("texPreviewEndpoint sent a PNG file of size zero. filename: " . $name); }
    $fp = fopen($name, 'rb');
    if ($fp == FALSE)         { throw new Exception ("texPreviewEndpoint could not open PNG file " . $name ); }

   if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: in try block before HEADER \n");}

    // add some headers to the response to support debugging and further handling
    header ("X-Latex-Hash:".$hash);  
    header ("X-Parsifal-Width-Latex-Cm-Was:".$widthLatexCm);
    header ("X-Parsifal-Available-Pixel_Width-Was:".$availablePixelWidth);
    header ("X-Parsifal-Gamma-Used:".$gamma);
    header ("X-Parsifal-Scale-Used:".$gamma);  
    

// ERROR HANDLING FOR THE TEX PREVIEW ENDPOINT ALONE 
    if ($retval == 0) { header ("X-Parsifal-Error:None"); }
    else              { header ("X-Parsifal-Error:Soft"); 
            $insideError  = false;
            $errorDetails = "";
            foreach ($retval as $infoline) {
              if ( strcmp ($infoline, ERROR_PARSER_START) == 0) {$insideError = true; continue;}
              if ( strcmp ($infoline, ERROR_PARSER_END  ) == 0) {$insideError = false; continue;}
              if ($insideError) {
                $hpos = strpos ($infoline, $hash);
                if ($hpos) { $infoline = substr ($infoline, $hpos + 48); }
                $errorDetails .= $infoline;}
            }
      
            $errorDetails = str_replace ( array ("\r", "\n", "\"",  ":"), "", $errorDetails); 
      
            header ("X-Parsifal-ErrorDetails:".$errorDetails);  // only: if we want to send more detailed errors to client alread in case of soft errors   
    }  

    header("Content-type:image/png");  header("Content-Length: " . filesize($name));  // set MANDATORY http reply headers
    fpassthru($fp); 
    fclose ($fp);
  } catch (Exception $ex) { self::renderError ("texPreviewEndpoint: Irrecoverable error: ", $ex, $hash); }
  if ($VERBOSE) {self::debugLog ("texPreviewEndpoint: returns from call for " . $tag . " widthLatexCm: ". $widthLatexCm . "  availablePixelWidth: ". $availablePixelWidth . " \n");}
}



// return exception and error information; use as error handler in all client render methods; provides text, recipient decides on wrapping html etc.
// $add: additional info,    $ex: exception thrown   $nameBase additional info which hash etc was affected
static function renderError ($add, $ex, $nameBase) {
  $txt = $ex->getMessage()."\nhash=$nameBase\n".$ex->getTraceAsString();  
  header ("X-Latex-Hash:".$nameBase); 
  header ("X-Parsifal-Error:Hard"); 
  header ("Content-type:text/html"); 
  header ("Content-Length: " .strlen( $txt) );
  echo $txt;
  self::debugLog ("renderError called: " . $add. "  " . $ex->getMessage() . "\n");
}  
  
// clean up all files belonging to a specific hash  
static function cleanUp ($hash) {
  $CACHE_PATH = CACHE_PATH;     
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.tex");  
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.aux");    
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.aux");    
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.log");   
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.out");   
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.png");   
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.pdf");   
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.html");  
  unlink ( "$CACHE_PATH$hash_pc_pdflatex.mrk");                  
}
   
  
// clean up all files in $CACHE_PATH, independently of the hash
static function cleanUpAll () {
  $CACHE_PATH = CACHE_PATH;
  $VERBOSE = true;
  if ($VERBOSE) {self::debugLog ("cleanUpAll called \n");}
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.tex");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.tex files for deletion \n");}  array_map ("unlink", $files);
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.aux");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.aux files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.log");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.log files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.out");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.out files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.png");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.png files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.pdf");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.pdf files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.html"); $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.html files for deletion \n");}  array_map ("unlink", $files);  
  $files = glob ( $CACHE_PATH."*_pc_pdflatex.mrk");  $count = count ( $files ); if ($VERBOSE) {self::debugLog ("found $count _pc_pdflatex.mrk files for deletion \n");}  array_map ("unlink", $files);              
}
  

  
  static function debugLog ($text) {
    if($tmpFile = fopen( LOG_PATH, 'a')) {fwrite($tmpFile, $text); fclose($tmpFile); }  // NOTE: close immediatley after writing to ensure proper flush
    else {throw new Exception ("debugLog in TexProcessor could not log"); }
  }
  
  
  static function errorLog ($text) {
    if($tmpFile = fopen( ERR_PATH, 'a')) {fwrite($tmpFile, $text);  fclose($tmpFile); } 
    else {throw new Exception ("errorLog in TexProcessor could not log"); }
  }  



// TODO: missing
private static function ensureFmtFile ($fmt) {
  $articleName = "MediaWiki:ParsifalTemplate/$fmt";
}


// modify raw tex input for various purposes
private static function modifyTex ( string $rawContent, string $tag, string $mode, $ar = array() ) : string {


/*
 \\dref\{([^{}]*)\}
 */


  $callback = function ($matches) {
//    self::debugLog ( "\n\n Callback found " . count($matches) . " matches \n");
    foreach ($matches as $value) {
      
    }
  };

 $newContent =  preg_replace_callback ("/\\dref\{([^{}]*)\}/", $callback, $rawContent);
  
//  self::debugLog ( "\n\n New contents. " . $newContent . " \n------------------\n\n");


  return $newContent;

}



#region generateTex
/** determine hash code of content and if no TeX file is there, build one
 *    $rawContent          string containing latex content
 *    $tag                 string with tagname of xml tag /
 *    $mode                the mode tag, i.e.  "" or "pc_latex" or "pc_pdflatex"  which controls how we inject the raw content into the template or precompilation
 *    $ar                  array of key => value form with the attributes
 */
private static function generateTex ( string $rawContent, string $tag, string $mode, $ar = array(), string &$cookedContent = null ) : string {
  $VERBOSE       = false;
  $CACHE_PATH    = CACHE_PATH;
  $TEMPLATE_PATH = TEMPLATE_PATH;  $LATEX_FORMAT_PATH = LATEX_FORMAT_PATH;  $PDFLATEX_FORMAT_PATH = PDFLATEX_FORMAT_PATH;
  
  $rawContent = self::modifyTex ( $rawContent, $tag, $mode, $ar);

  if ($VERBOSE) {self::debugLog ("generateTex: attribute array is: ".print_r ($ar, true). "\n");}
  ksort($ar);                                             // sort array on keys, in place, so that the hash becomes independent on the sequence 
  $stringAr = print_r ($ar, true);                        // go from php array to a full string representation
  $hash     = md5 ($tag.$stringAr.$rawContent);           // derive a unique file name - need dependency on tag, content and attributes as all of this has impact on looks.

  switch ($mode) {
    case "pc_latex":           // we use a precompilation made for the latex processor     
      $fmt="$LATEX_FORMAT_PATH$tag";     
      $resultFile = "$CACHE_PATH{$hash}_$mode.tex";  
      if (!file_exists ("$fmt.fmt")) { Parsifal::reconstructFormat ("ParsifalTemplate/$tag");}
      ASSERT_FILE ("$fmt.fmt");    
   
      $endPreambleStuff   = self::generateEndPreambleStuff ($ar, $tag);
      $beforeContentStuff = self::generateBeforeContentStuff ($ar, $tag);
      $template = "%&$fmt\n".$endPreambleStuff.$beforeContentStuff;    
      break;

    case "pc_pdflatex":        // we use a precompilation made for the pdflatex processor
      $fmt="$PDFLATEX_FORMAT_PATH$tag";  $resultFile = "$CACHE_PATH{$hash}_$mode.tex";  
      if (!file_exists ("$fmt.fmt")) { Parsifal::reconstructFormat ("ParsifalTemplate/$tag");}
      ASSERT_FILE ("$fmt.fmt");    
      $endPreambleStuff   = self::generateEndPreambleStuff ($ar, $tag);
      $beforeContentStuff = self::generateBeforeContentStuff ($ar, $tag);
      $template = "%&$fmt\n".$endPreambleStuff.$beforeContentStuff;   
      //$template = $endPreambleStuff.$beforeContentStuff;   // TODO BRANCH
      break;

    case "": 
      $resultFile       = "$CACHE_PATH{$hash}$mode.tex";                                      // only in THIS case no underscore - above we NEED underscore
      $templateFileName = "$TEMPLATE_PATH$tag.tex";  
      ASSERT_FILE ($templateFileName);
      $template         = file_get_contents ($templateFileName) . $endPreambleStuff;   // TODO: WHO does the begin document now ???
      break;
    default: 
      throw new Exception ("generateTex: received illegal mode $mode");
  }

  if (strpos ($template, MAGIC_LINE) == FALSE)   {
    $msg = "generateTex: could not find MAGIC_LINE in template file " . $templateFileName . "\nFor more information see logfile at " .LOG_PATH. "\n";
    self::debugLog ($msg);
    self::debugLog ("---------Template:\n" .$template. "\n-----END-----\nMAGIC_LINE IS:\n\n" . MAGIC_LINE . "\n\n");
    throw new Exception ($msg); }  // check for MAGIC_LINE in template
  $markerStart = "\\typeout{" . ERROR_PARSER_START . "}";  // form a marker which helps the error parser in the log file detect the beginning of the document
  $markerEnd   = "\\typeout{" . ERROR_PARSER_END   . "}";  // form a marker which helps the error parser in the log file detect the beginning of the document


  $text = str_replace ( MAGIC_LINE, $markerStart.$rawContent.$markerEnd, $template);     // replace the MAGIC_LINE by the current input; maximally one replacement

  if ($cookedContent == null) {
    self::debugLog ("generateTex: received a null, writing file \n");
    if ( $fileObject = fopen( $resultFile, 'w') ) { fwrite($fileObject, $text);  fclose($fileObject); }
    else { throw new Exception ("generateTex: error writing result file $resultFile " ); }
  }
  else { $cookedContent = $text;
    self::debugLog ("generateTex: received non-null, returning data: $text\n ------------------------------------------------------------------------\n\n");
  }

  return $hash;
}
#endregion


/** GENERATE PNG from DVI via DVIPNG tool
 *  assume there is a $hash.dvi file, generate a $hash$final.png file from it using the dvipng driver
 *    $hash    
 *    $dpi     dpi value to be used
 *    $gamma   gamma value to be used
 *    $final   part of the result file name, use to distinguish different runs with different parameters etc.
 */
private static function Dvi2Png ($hash, $dpi, $gamma=1.5, $inFinal="", $outFinal="") {
  $VERBOSE = true;  $CACHE_PATH = CACHE_PATH;
  ASSERT_FILE ("$CACHE_PATH$hash$inFinal.dvi");
  $cmd = DVIPNG_BINARY . " -T tight -p=1 -l=1 -D $dpi -gamma $gamma -o $CACHE_PATH$hash$outFinal.png  $CACHE_PATH$hash$inFinal.dvi ";    
  if ($VERBOSE) {$startTime = microtime(true);  self::debugLog ("Dvi2Png started for $hash, command is: " . $cmd . "\n");}
  // $output = null;  $retVal = null;  // do not need error messaging here
  exec ( $cmd );  
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Dvi2Png. DURATION: " . $duration . "\n"); }   
}

/** GENERATE PNG from PDF via mutool. Transforms $hash.pdf into $hash$final.png
 */
private static function Pdf2PngMT ($hash, $dpi, $inFinal, $outFinal) {
  $VERBOSE = true;  $CACHE_PATH = CACHE_PATH;
  $cmd = MUTOOL. " convert -O resolution=$dpi -o $CACHE_PATH$hash$outFinal.png $CACHE_PATH$hash$inFinal.pdf  1-1";     // 1-1 is the page range    
  if ($VERBOSE) {$startTime = microtime(true);  self::debugLog ("Pdf2PngMT started for $hash, command is: " . $cmd . "\n");}
  // $output = null;  $retVal = null;  //  exec ( $cmd, $output, $retVal );  
  exec ( $cmd );  
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Pdf2PngMT. DURATION: " . $duration . "\n"); }  
  // if ($VERBOSE) {self::debugLog ("  return value $retVal  output ". print_r ($output, true));} 
}




private static function getPdfSize  ($hash, &$width, &$height) {
  $CACHE_PATH = CACHE_PATH;  $PY_PATH = PY_PATH;
  $cmd = "$PY_PATH/get-size.py $CACHE_PATH${hash}_pc_pdflatex"; 

  $exists = (file_exists ("$CACHE_PATH${hash}_pc_pdflatex.pdf") ? "EXISTS" : "MISSING");
  $size = filesize ( "$CACHE_PATH${hash}_pc_pdflatex.pdf");

  $retval = 0;
  $last =  exec($cmd, $output, $etvarl);
  $output = implode("\n", $output);
//  $res = system ($cmd, $retval);
  list($width, $height) = sscanf($output, "%f %f");
  self::debugLog ("getPdfSize reported: $width x $height at file size $size with file $exists\n");
  return;
}




/** GENERATE PNG from PDF via mutool. Transforms $hash$inFinal.pdf into $hash$inFinal.png */
private static function Pdf2PngHtmlMT ($hash, $scale, $inFinal, $outFinal, &$duration = null) {
  $VERBOSE = true; 
  $JS_PATH = JS_PATH;  $CACHE_PATH = CACHE_PATH;  $PY_PATH = PY_PATH;

//  $cmd = MUTOOL. " run  $JS_PATH/my-device.js $scale $CACHE_PATH$hash$inFinal $CACHE_PATH$hash$outFinal ";      // COMMAND:   /usr/bin/mutool  run 

  $cmd = "$PY_PATH/make.py $scale $CACHE_PATH$hash$inFinal $CACHE_PATH$hash$outFinal "; 
 if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: executor $hash starting: \n"); }
  $retVal = TeXProcessor::executor ($cmd, $output, $error, false, $duration);
 if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: executor $hash finished: \n"); }
  
  //if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: mutool execution for scale=$scale hash=$hash had a duration of: ".$duration . "\n"); }
  //if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: mutool run output $hash shellexecutor: \n".$output); }
  // TODO: better error handling
}



////// THIS is the background variant !!!

/** GENERATE PNG from PDF via mutool. Transforms $hash$inFinal.pdf into $hash$inFinal.png */
private static function Pdf2PngHtmlMT_BG ($hash, $scale, $inFinal, $outFinal, &$duration = null) {
  $VERBOSE = true; $JS_PATH = JS_PATH;  $CACHE_PATH = CACHE_PATH;  $PY_PATH = PY_PATH;
  $cmd = MUTOOL. " run  $JS_PATH/my-device.js $scale $CACHE_PATH$hash$inFinal $CACHE_PATH$hash$outFinal ";      // COMMAND:   /usr/bin/mutool  run  
  // $output = null;  $retVal = null;  $error = null;
  //$retVal = TeXProcessor::executor ($cmd, $output, $error, true, $duration);
  // TODO: error handling
  exec ( $cmd . " > /dev/null 2>&1 & " );
}



// this is the variant returning the html / svg / annotation code directly
private static function Pdf2PngHtmlMT_RET ($hash, $scale, $inFinal, $outFinal, &$duration = null) {
  $VERBOSE = true; 
  $JS_PATH = JS_PATH;  $CACHE_PATH = CACHE_PATH;  $PY_PATH = PY_PATH;

//  $cmd = MUTOOL. " run  $JS_PATH/my-device.js $scale $CACHE_PATH$hash$inFinal $CACHE_PATH$hash$outFinal ";      // COMMAND:   /usr/bin/mutool  run 

  $cmd = "$PY_PATH/make2.py $scale $CACHE_PATH$hash$inFinal $CACHE_PATH$hash$outFinal "; 
 if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: executor $hash starting: \n"); }
  $retVal = TeXProcessor::executor ($cmd, $output, $error, false, $duration);
 if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: executor $hash finished: \n"); }
  
  if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: mutool execution for scale=$scale hash=$hash had a duration of: ".$duration . "\n"); }
  if ($VERBOSE)  { self::debugLog ("\n TeXProcessor:: mutool run output $hash shellexecutor: \n".$output); }
  // TODO: better error handling


  return $output;
}















private static function manuBoth ( $hash1, $inFinal1, $note1, $hash2, $scale2, $inFinal2, $outFinal2) {
  $VERBOSE = true;  $CACHE_PATH = CACHE_PATH;  $JS_PATH = JS_PATH;  
  ASSERT_FILE ("$CACHE_PATH$hash1$inFinal1.tex"); 
  // if ($VERBOSE) {self::debugLog ("Tex2Pdf sees the following environment: \n".print_r (getenv(), true) );}  // uncomment to check the active environment
  $cmd1 = "pdflatex  --interaction=nonstopmode  -file-line-error-style -output-directory=$CACHE_PATH $CACHE_PATH$hash1$inFinal1.tex  >/dev/null 2>&1  "; 
  $cmd2 = MUTOOL. " run  $JS_PATH/my-device.js $scale2 $CACHE_PATH$hash2$inFinal2 $CACHE_PATH$hash2$outFinal2 >/dev/null 2>&1 ";  
  $cmd = "{ " . $cmd1 . " ; " . $cmd2 . " ;} >/dev/null 2>&1 & " ;
 
  $output = null;  $retval = null;   
  $res = exec ( $cmd, $output, $retval );    
 // if ($VERBOSE) { $endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed manuBoth ($note1). DURATION: $duration RETURN: $retval \n"); }   

//  file_put_contents ( $CACHE_PATH . $hash. "$inFinal.mrk", ( $retval != 0 ? "ERROR" : "" ) ); // write ERROR into .mrk file if there is an error to know about this later when we only access file system // TODO !!!!!!!!!!!!!!!!!!!!
  if (! file_exists ( "$CACHE_PATH$hash2$inFinal2.pdf") )  {
    throw new Exception ("Tex2Pdf: Did not generate $CACHE_PATH$hash2$inFinal2.pdf for this content. Probably TeX error or transient problem while editing.");} 
//  return ($retval == 0 ? 0 : $output);  
}





/** GENERATE SVG from PDF via mutool. Transforms $hash.pdf into $hash$final.png
 */
private static function Pdf2SvgMT ($hash, $dpi, $final="_mt", $inFinal="_pdflatex") {
  $VERBOSE = true;
  $CACHE_PATH = CACHE_PATH;
  $cmd = MUTOOL. " convert -O resolution=$dpi -o $CACHE_PATH$hash$final.svg $CACHE_PATH$hash$inFinal.pdf  1-1";     // 1-1 is the page range    
  if ($VERBOSE) {$startTime = microtime(true);  self::debugLog ("Pdf2SvgMT started for $hash, command is: " . $cmd . "\n");}
  // $output = null;  $retVal = null;  //  exec ( $cmd, $output, $retVal );  
  exec ( $cmd );  
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Pdf2SvgMT. DURATION: " . $duration . "\n"); }  
  // if ($VERBOSE) {self::debugLog ("  return value $retVal  output ". print_r ($output, true));} 
}





/** GENERATE PNG from PDF via GHOSTSCRIPT
 *   devices:   pngmono   pngray  png256    png16
 */
 private static function Pdf2PngGS ($hash, $dpi, $final="_gs", $device="pngmono", $inFinal="_pdflatex") {
   $VERBOSE = true;
   $CACHE_PATH = CACHE_PATH;
   $cmd = GHOSTSCRIPT . "  -dSAFER -dBATCH -dNOPAUSE -sDEVICE=$device -r$dpi -sPageList=1 -o $CACHE_PATH$hash$final.png $CACHE_PATH$hash$inFinal.pdf  ";    
   if ($VERBOSE) {$startTime = microtime(true);  self::debugLog ("Pdf2PngGS started for $hash, command is: " . $cmd . "\n");}
   // $output = null;  $retVal = null;  // do not need error messaging here
   exec ( $cmd );  
   if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Pdf2PngGS. DURATION: " . $duration . "\n"); }   
 }




/** Generate dvi from tex using latex processor (works for ANY tex file, independently of precompilation settings) */

private static function Tex2DviLatex ($hash, $inFinal="") {
  $VERBOSE = true;
  $CACHE_PATH = CACHE_PATH;
  ASSERT_FILE ("$CACHE_PATH$hash$inFinal.tex");
  $cmd = LATEX_COMMAND . " -output-dir=$CACHE_PATH $CACHE_PATH$hash$inFinal.tex";              // MUST do a cd, since latex may generate some files in the local directory // TODO????????????????????????????? use build directoiry ???????
  if ($VERBOSE) {$startTime = microtime(true); self::debugLog ("Tex2DviLatex ($inFinal) for $hash$inFinal.tex, command is: $cmd \n");} 
  $output = null;  $retVal = null;
  exec ( $cmd, $output, $retVal ); 
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Tex2DviLatex $inFinal for $hash$inFinal.tex. DURATION: $duration \n"); }

  // we could have partially usable LaTeX output and still have errors. This can be seen in the exit code and is signaled in the .mrk file across different endpoint invocations
  file_put_contents ( $CACHE_PATH . $hash. "$inFinal.mrk", ( $retVal != 0 ? "ERROR" : "" ) );
  if (! file_exists ( "$CACHE_PATH$hash$inFinal.dvi") )  {throw new Exception ("Tex2DviLatex: $inFinal Could not generate ANY DVI for this content. Probably TeX error or transient problem while editing.");} 
}

// CAVE: BUGS
/** Generate dvi from tex using pdflatex processor (works for ANY tex file, independently of precompilation settings) */
/** CAVE: It does not work to use pdflatex in dvi mode with a format which has been precompiled for pdf mode */
private static function Tex2DviPdflatex ($hash, $inFinal="") {
  $VERBOSE = true;
  $CACHE_PATH = CACHE_PATH;
  $cmd = PDFLATEX_COMMAND . " -output-format=dvi -output-dir=$CACHE_PATH $CACHE_PATH$hash$inFinal.tex";              // MUST do a cd, since latex may generate some files in the local directory // TODO????????????????????????????? use build directoiry ???????
  if ($VERBOSE) {$startTime = microtime(true); self::debugLog ("Tex2DviPdflatex command is: $cmd \n");} 
  $output = null;  $retVal = null;
  exec ( $cmd, $output, $retVal ); 
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed Tex2DviPdflatex for $hash$inFinal.tex. DURATION: $duration \n"); }

  // we could have partially usable LaTeX output and still have errors. This can be seen in the exit code and is signaled in the .mrk file across different endpoint invocations
  file_put_contents ( $CACHE_PATH . $hash. "$inFinal.mrk", ( $retVal != 0 ? "ERROR" : "" ) );
  if (! file_exists ( "$CACHE_PATH$hash$inFinal.dvi") )  {throw new Exception ("Tex2DviPdflatex: Did not generate $CACHE_PATH$hash$inFinal.dvi for this content. Probably TeX error or transient problem while editing.");} 
}







// TODO: dedpulicate this code with the same code in DanteBackup

// $cmd:       command to be executed
// $output:    captures stdout
// $error:     captures stderr
// $duration:  captures execution time in microseconds
// $verbose:   if true, write an invocation and completion log to debug
// $duration:  optional variable which will be set to the duration of the call, if provided by the caller
// return:     return value of the command
// CAVE 1: We MUST store the value of proc_open somewhere and we must release ressource using proc_close, otherwise things may go wrong
// CAVE 2: Similar with the pipes, which MUST be prepared, read and properly closed.

public static function executor ( string $cmd, &$output, &$error, $verbose=false, &$duration = null ) {
  if ($verbose) {$cfn = debug_backtrace()[1]['function']; self::debugLog ( "$cfn calling shell executor\n"); }  // get name of the calling function

  $startTime  = microtime(true); 
  $proc       = proc_open($cmd,[ 1 => ['pipe','w'], 2 => ['pipe','w'],], $pipes);
  $output     = stream_get_contents($pipes[1]); fclose($pipes[1]);
  $error      = stream_get_contents($pipes[2]); fclose($pipes[2]);
  $closeParam = proc_close($proc);

  $duration   = microtime (true) - $startTime;
  if ($closeParam != 0) { self::debugLog ("Executor of $cmd closed with a non-zero return from proc_close: $closeParam\n" );}
  if ($verbose) { self::debugLog ( "$cfn executor call completed.\n    Command: $cmd\n    DURATION: $duration\n    OUTPUT:--------\n$output\n--------\n    ERROR: $error\n" ); }
  return $closeParam;
}



// TODO migrate the debug comments in most functions which use the executor into the executor with a specifi variable requesting verbosity as parameter to the executor - as well as commentary on the caller 

/** Transform TeX into PDF
 *
 *  ERROR CONDITIONS:
 *    Hard error: Throw
 *    Soft error: Return non-zero (retval): Caller may show link to parsed latex error log
 *    No error: Return zero
 *   
 *    Returns:
 *      0 no error at all
 *      1 tex returned error, but we did not find an error message
 *    127 command not found
 *    string  error text as the error parser felt fit to parse it
 */
private static function Tex2Pdf ($hash, $inFinal, $note) {
  $VERBOSE = false;  
  $CACHE_PATH = CACHE_PATH;
  $texFileName = "$CACHE_PATH$hash$inFinal.tex";
  ASSERT_FILE ($texFileName); 
  // if ($VERBOSE) {self::debugLog ("Tex2Pdf sees the following environment: \n".print_r (getenv(), true) );}  // uncomment to check the active environment


////// TODO:   move to a -fmt based call (needs an adaptation of the generated tex)
////// TODO:   want a redirect of the output - to a file, or maybe different

//  $cmd = "pdflatex  --shell-escape --interaction=nonstopmode  -file-line-error-style -ipc -fmt=$CACHE_PATH/../extensions/Parsifal/formats_pdflatex/amsmath -output-directory=$CACHE_PATH $texFileName > $CACHE_PATH/LOGG"; 

// working was:
  $cmd = "pdflatex  --shell-escape --interaction=nonstopmode  -file-line-error-style -output-directory=$CACHE_PATH $texFileName"; 



  if ($VERBOSE || true) { self::debugLog ("Tex2Pdf started ($note) for $hash$inFinal, command is: $cmd \n");}   
  $retval = TeXProcessor::executor ( $cmd, $output, $error, false );
  //  127   a fundamental error such as command not found
  //   1   a small tex error, but might be worth mentioning
  //   0   no error at all
  
  if ($retval == 0)        { $ret="";}
  else if ($retval == 127) { $ret="System error. Code 127. Check logs or inform manufacturer.";}
  else if ($retval == 1)   {
    $logfileContents = file_get_contents ( $CACHE_PATH . $hash . "_pc_pdflatex.log");
    if ($logfileContents === false) { $ret= "Tex signalled an error but we could not find an error file. Hash is ".$hash; }
    else {
      $index=strpos ($logfileContents, $texFileName.":");
      $substring = substr($logfileContents, $index);
      $index=strpos ($substring,":");
      $substring = substr ($substring, $index+1);
      $newlinePos = strpos($substring, "\n");
      if ($newlinePos !== false) { $ret= substr($substring, 0, $newlinePos); } else { $ret = $substring; }
  } }
  else                     { $ret=" Unknown errorcode received from Tex driver. Value is ".$retval; }

  file_put_contents ( $CACHE_PATH . $hash. "$inFinal.mrk", $ret ); // write ERROR into .mrk file if there is a soft error to know about this later when we only access file system
  if ($VERBOSE) {self::debugLog ("Tex2Pdf: Error: $ret \n");}

//  else {
//    throw new Exception ("Tex2Pdf: Looks like a hard error: \n ".  (file_exists ( "$CACHE_PATH$hash$inFinal.pdf") ? " Have a pdf file\n" : " Have NO pdf file\n" )  ." Executor return value: $res \n Output: $output \n Error: $error \n File: $CACHE_PATH$hash$inFinal.pdf\n");   
//  }
  return $ret;
}




private static function Tex2PdfPiped ($tex, $hash, $inFinal) {
  $VERBOSE = false;  
  $CACHE_PATH = CACHE_PATH;
  $texFileName = "$CACHE_PATH$hash$inFinal.tex";
  $cmd = "pdflatex  --shell-escape --interaction=nonstopmode  -file-line-error-style  -fmt=$CACHE_PATH/../extensions/Parsifal/formats_pdflatex/amsmath   -output-directory=$CACHE_PATH -jobname $hash$inFinal  > $CACHE_PATH/LOGG_PIPED"; 

  self::debugLog ("######## Tex2PdfPiped received: $tex \n");

  $tex=str_replace ("\n","", $tex);
  $tex = "\\documentclass{standalone}".$tex."\\end";

  $tex="\\begin{document}hi\\end{document}";

  $proc = proc_open( $cmd,  array(0 => array('pipe','r'), 1 => array('pipe','w')), $pipes, NULL );
  if (is_resource($proc)) {
   $written= fwrite($pipes[0], $tex, 10000);
    self::debugLog ("WRITTEN: $written\n\n");


   // fclose($pipes[0]);

//    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);
  proc_close($proc);

  }




}























/** GENERATE HTML from PDF with annotations using NODE-tool
 *  assume the existence of file   $hash.pdf    TODO: or different ??????
 *  return html text of annotation layer
 *  if $saveFile = true also save it as file $hash.html
 *
 */
static function generateHTML ($hash, $htmlScale =1, $horizontalDelta=0, $verticalDelta=0, $saveFile=true ) {
  $VERBOSE = false;  $CACHE_PATH = CACHE_PATH;  $PDF2HTML = PDF2HTML;
  $cmd = "$PDF2HTML $CACHE_PATH$hash_pdflatex.pdf  $CACHE_PATH$hash.html  $htmlScale  $horizontalDelta  $verticalDelta  >$CACHE_PATH$nameBase._html.stdout 2>$CACHE_PATH$nameBase._html.stderr";  
  if ($VERBOSE) {$startTime = microtime(true); self::debugLog ("generateHTML started for $hash, command is: $cmd \n");}     
  $output = null;  $retVal = null;
  exec ( $cmd, $output, $retVal ); 
  if (VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed generateHTML. DURATION: $duration \n"); }
  if ($saveFile) {file_put_contents ( "$CACHE_PATH$hash.html", $output );}
  return $output;
}


// TODO: why do we still have this function - AND the function Tex2Pdf as well (which we use in lazyRenderer ???)
// TODO: deprecate this one here ??  
/** GENERATE PDF via PDFLATEX  from  $hash.tex => $hash.pdf
 *  Assume there is a $has.tex file, generate a $hash_pdflatex.pdf file using pdflatex
 *  Used jobname is $hash_pdflatex in order to have all intermediary files (such as .aux, .log) seperate from other tex engines which might be in use as well
 *  returns exit value
 */
static function generatePDF ($hash) {
  $VERBOSE = true;
  $CACHE_PATH = CACHE_PATH;
  ASSERT_FILE ("$CACHE_PATH$hash.tex");
  $cmd = PDFLATEX_COMMAND ." -output-directory=$CACHE_PATH $CACHE_PATH$hash.tex ";  
  if ($VERBOSE) { $startTime = microtime(true); self::debugLog ("generatePDF started for $hash, command is: $cmd \n");}   
  $output = null;  $retVal = null;   
  $res = exec ( $cmd, $output, $retval );    
  if ($VERBOSE) {  $endTime = microtime (true); $duration = $endTime - $startTime; self::debugLog ("  completed generatePDF. DURATION: $duration \n"); }         
  return $retVal;
}

/** generate bounding box information from pdfFileName
 *  construct the filename from the $hash and an additional qualifier for the pdf as in  $hash$inFinal.pdf
 *    $hashFinal is $hash, possibly with a precompilation tag, uniquely defining the $hashFinal.pdf file we need here
 */
static function generatePdfBboxGS ($hashFinal) {
  $VERBOSE = true;
  $GHOSTSCRIPT = GHOSTSCRIPT;
  $CACHE_PATH = CACHE_PATH;
  ASSERT_FILE ("$CACHE_PATH$hashFinal.pdf");
  $cmd = "$GHOSTSCRIPT -dBATCH -dNOPAUSE -dQUIET -sPageList=1 $CACHE_PATH$hashFinal.pdf 2>&1";  // need the stderr to stdout redirect since ghostscript outputs this on stderr
  if ($VERBOSE) {$startTime = microtime(true); self::debugLog ("generatePdfBboxGS started for $hashFinal, command is: $cmd \n");}  
  $output = null; $retVal = null;
  $res = exec ($cmd, $output, $retval);  // output should now be an array, one item per line and we should get two elements, the normal and the hi res bounding box  
                                         // if we get more there is either an error or we have a multi-page pdf  the first is the normal bounding box and it has string format as in:  %%BoundingBox: 0 0 426 823
  if ($VERBOSE) { self::debugLog ("  output obtained from ghostscript is:" . print_r($output, true) . "\n");}
  $txtArray = explode (" ",$output[0]);
  
  if ( !str_starts_with ($txtArray[0], "%%BoundingBox") ) { throw new ErrorException ("generatePdfBboxGS: could not find ghostscript bounding box answer for $hashFinal. Output obtained was ".print_r($output, true)); }
  
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime; $outputTxt =  "1=".$txtArray[1]." 2=".$txtArray[2]."".$txtArray[3]."".$txtArray[4]; self::debugLog ("  completed generatePdfBboxGS for $hashFinal. DURATION: $duration  returned $retval RESULT: $outputTxt \n");}  
  $result = array ( "left" => $txtArray[1], "top" => $txtArray[2], "left" => $txtArray[3], "left" => $txtArray[4]);
  return $result; 
}


/** get a width, height array for the Png file generated from the latex-dvi-dvipng path 
 *  assuming that the $hash.tex, $hash.dvi and $hash.png already exist or get 0 if file does not exist
 */
static function getSizeFromDviPng ($hash) {
  $CACHE_PATH = CACHE_PATH;
  $path = $CACHE_PATH.$hash.".png";  
  if (file_exists ($path))  {$ims = getimagesize ( $path ); $ims["width"] = $ims[0]; $ims["height"] = $ims[1];} else { $ims = 0;}  
  return 0;
}

/** assume the existence of $hash_pdflatex.pdf, produce a png, use it for cropping and produce an adjusted html
*/
static function generateNodePngHtml ($hash, $scale=2.54) {
  $VERBOSE = true;
  $CACHE_PATH = CACHE_PATH;
  $htmlScale = $pngScale = $scale;
  $cmd = NODE_BINARY . " " . NODE_SCRIPT. " " .  $CACHE_PATH . $hash. "_pdflatex.pdf " . $CACHE_PATH . $hash. "_node.png " . $CACHE_PATH . $hash. ".html " .  $pngScale . " " . $htmlScale  ." >". $CACHE_PATH . $hash."_node.stdout 2>" . $CACHE_PATH . $hash."_node.stderr";  
  if ($VERBOSE) {$startTime = microtime(true); self::debugLog ("generateNodePngHtml started for $hash, command is: $cmd \n");}  
  $output = null;  $retVal = null;  
  $res = exec ( $cmd, $output, $retval );
  if ($VERBOSE) {$endTime = microtime (true); $duration = $endTime - $startTime;  self::debugLog ("  completed generateNodePngHtml - DURATION: " . $duration . "  retval=".$retval."  res=".$res." \n"); }    
}



/////// TODO: there still is a permission problem with this thing here 

// TODO: in the background we want to do additional resolutions and themes 
/*
static function completeInBackground ($hash) {
  $CACHE_PATH = CACHE_PATH;
  //////// TODO: ADJUST and improve these settings !
  $pngScale = "2.54";    // if   "0"  we produce no png
  $htmlScale = "2.54";   // if  "0"  produce no html
  
  // NOTE: The reason that we do this so complicated is that we need a particular sequence of jobs to run in the background of php. 
  // We never got that running in *all* details, especially setting particular paths and shell variables for pdflatex and getting proper redirects and debug info

  $sheBang = "#!/bin/sh";
  $cd = "cd ".CACHE_PATH;
  $cmdPdflatex =  PDFLATEX_COMMAND . " -jobname " . $hash . "_pdflatex " .  $CACHE_PATH . $hash . ".tex " . ">".$CACHE_PATH.$hash."_pout  2>".$CACHE_PATH. $hash. "_perr";
  $cmdNode = NODE_BINARY . " " . NODE_SCRIPT. " " .  $CACHE_PATH . $hash . "_pdflatex.pdf " . $CACHE_PATH . $hash . "_node.png " . $CACHE_PATH . $hash . ".html " .  $pngScale . " " . $htmlScale  ." >". $CACHE_PATH . $hash."_nout 2>" . $CACHE_PATH . $hash."_nerr";  

  $shellFileName = $CACHE_PATH . $hash . ".sh";
  touch ($shellFileName);
  chmod ( $shellFileName, 0774);  
  if($shellFile = fopen( $shellFileName, 'w')) {
    fwrite($shellFile, $cd."\n".$sheBang."\n".$cmdPdflatex."\n".$cmdNode);  
    fclose($shellFile);
  } 
  exec ($shellFileName . " >/dev/null 2>/dev/null & ");    // kickoff and run in background.  ///// TODO: we do not want to keep output and stderr here ????
                                                           // MUST have a redirect of the stdout and stderr or we will block/https://stackoverflow.com/questions/14555971/php-exec-background-process-issues
}
*/


} // END of class


?>
