<?php

// implements an additional action to download an entire MediaWiki page containing only / mostly LaTeX
// as a single PDF file


function debugLog ($text) {
  if($tmpFile = fopen( "extensions/Parsifal/log/FullPageLog", 'a')) {fwrite($tmpFile, $text);  fclose($tmpFile);}   // TODO: log file into config.php !!
  else {throw new Exception ("debugLog in Push could not log"); }
}


debugLog ("Hallo Full Page");



class FullPageAction extends Action {
  
  public function __construct( $article, $context ) {  debugLog ("will construct FullPageAction\n"); 
    // debugLog ("Article is:" . print_r ($article, true));  // Article is a WikiPage object
    // debugLog ("Context is:" . print_r ($context, true));  // too large for dumping
    parent::__construct( $article, $context );
    debugLog ("did construct FullPageAction\n"); 
  }
  
//  public function doModuleExecute () {}
  
  public function getName () {return "fullpage";}
  
  
  public function fullPageConversion ( $raw ) {
    
    // convert the = markings to amsmath encapsulated section portions
    $sub = '[a-zA-Z0-9äöüÄÖÜß\\_\\-\\s]';  // portion of regular expression matching the Mediawiki section name
    $pattern      = array ( '/^==\\s*('.$sub.'*)==/m', '/^===\\s*('.$sub.'*)===/m', '/^====\\s*('.$sub.'*)====/m' );
    $replacements = array ( '<amsmath>\\n\section{\\1}</amsmath>', '<amsmath>\\n\\subsection{\\1}</amsmath>', '<amsmath>\\n\\subsubsection{\\1}</amsmath>' );
    
    $raw = preg_replace ( $pattern, $replacements, $raw );
    
    $raw = "<rootelement>" . $raw . "</rootelement>";  // package into rootelement so as to have valid xml
    $xml=simplexml_load_string($raw);                  // parse that xml
    $dom = dom_import_simplexml ($xml);

    // remove Category links  TODO
    // insert Title of page TODO
    // insert copyright, url, date of manufacturing, oldid version number, date of last editing TODO

    // encapsulate non-amsmath text portions somehow into amsmath nodes in order not to lose the relevant information
    $doc = new DomDocument;                                         // make a new DomDocument as one is needed for xpath to work
    $doc->appendChild($doc->importNode($dom, true));                // import out dom node
    $xpath = new DOMXpath($doc);
    $myTextNodes = $xpath->query ('//text()[not(parent::amsmath)]');
    
      $REPORT = "";
    
    // remaining text nodes are encapsulated as well
    $REPORT .= "NUMBERS of TEXT PARTS: " .$myTextNodes->count() ;
    foreach ($myTextNodes as $textNode) {
      $trimmed = trim($textNode->wholeText);
      if (strlen ($trimmed) == 0) {continue;}                                          // ignore white-space only
      $newNode = $doc->createTextNode ( "REMAINING INFO: " . $trimmed . " BRACKET");
      $newElem = $doc->createElement ("amsmath");
      $newElem->appendChild ($newNode);
      $oldNode = $textNode;
      $parent  =  $textNode->parentNode;
      $parent->replaceChild ($newElem, $oldNode);
    }
    
  
    $elements = $doc->getElementsByTagName('amsmath');     // iterate over all amsmath elements
    // $data = array();
    foreach($elements as $node){
      foreach($node->childNodes as $child) {
        // $data[] = array($child->nodeName => $child->nodeValue);
        
        // do checks to see if we require a specific "proof", or similar header here
        $atts = $child->attributes;           // get all attributes as named node map
        if ($atts) {  // only if there are attributes at all
        foreach ($atts as $key => $val) {     // iterate
          // TODO !!!!!!!!!!!!!!
          
        }
      }
        
        $REPORT .= $child->nodeValue;
      }
    }
        
    
    // remove the amsmath - or convert the c- and o- parts accordingly - maybe even call a XML parser ?!?!
    
    return $REPORT;
  }
  
  
  public function show () {
    $output = $this->getOutput();
    $title  = $this->getTitle();
      debugLog ("Title found is " . $title);
    // $myTitle   = Title::newFromText( $titleText, NS_MEDIAWIKI );
    $article = new Article( $title );
    $content  = $article->getPage()->getContent()->getNativeData();  
      debugLog ("Content found is " . $content);
   // $content = $wikiPage->getContent( RevisionRecord::RAW );
   // $text = ContentHandler::getContentText( $content );
   // debugLog ("Page found is " . $text);
    
    // build the page to which we then will redirect
     
     
     // WE MUST ROLL our own parser as it looks like - or rather, combine stuff somehow ?!?!
     
     /*
     $pageReference = $title;  // try ß
     
     
    $user = $this->getUser();
    //$lang = $this->getLang(); // not working ?!?
    $parserOptions = new ParserOptions ( $user);
    
   // $parserOptions->setOption ("requestingParsifalFullPage", true);
     
    // $localParser = MediaWikiServices::getInstance()->getParserFactory()->create(); // only since 1.32
    
    
    $localParser = new Parser ();
    $localParser->calledFromParsifalFullPage = true; // dynamically set property to get the right interception upstairs // TODO if really needed ?!?!
    
    $parsed = $localParser->parse ($content, $pageReference, $parserOptions);
    debugLog ("\n\n-------------------------------\n\n Parsed getText is: " . $parsed->getText());
    debugLog ("\n\n-------------------------------\n\n Parsed getRawText is: " . $parsed->getRawText());    
    
    debugLog ("\n\n-------------------------------\n\n Parsed getSections is: " .print_r ( $parsed->getSections(), true  )  );        
    
    //   debugLog ("\n\n-------------------------------\n\n Parsed tojsonarray is: " . print_r ($parsed->toJsonArray(), true)); // not yet available in my current version
    
    debugLog ("\n\n-------------------------------\n\n Parsed is: " . print_r ($parsed, true));
    
    $raw = $parsed->getText();
    
    
    */
    
    $raw = $this->fullPageConversion ($content);
    $output->prependHTML ("<pre>" . $raw . "</pre>");
    
    
    // $output->redirect ("https://blog.fefe.de" ); 
    // works  // should delete it later maybe to prevent old contents from running around on filesystem
  }
  
}







