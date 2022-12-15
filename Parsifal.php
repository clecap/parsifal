<?php

/***
 * 
 * PHP calculates hash value and generates an img tag with a src containing the hash value (as postfix of preview.php) and a data-texsrc dataset
 * img attemtps loading via preview.php with hash value as query postfix; this may succeed and then is the normal display speef we currently have
 * if the img loading fails the onerror handler faults in the image, taking the latex source from the data-texsrc.
 * 
 */
  

use MediaWiki\MediaWikiServices;

require_once ("config.php");                // include path configuration
require_once ("php/TexProcessor.php");


class Parsifal {                            // glue class of the extension

  public static function onParserFirstCallInit( Parser $parser ) {                                    // Register parser callback hooks
    global $initialHashsUsed;
    $VERBOSE = true;
    $title = $parser->getTitle();
    if ($VERBOSE) {TeXProcessor::debugLog( "Parsifal::onParserFirstCallInit for page of title: ".$title.  " \n");}
    
    ///// ???? not clear what this is ??????
    if (property_exists ($parser, "calledFromParsifalFullPage") && $parser->calledFromParsifalFullPage) {
        $parser->setHook ( 'block',            function ($in, $ar, $parser, $frame) { return "<div style='width:619px;color:red;'>---------------" . $in . "</div>";  }    );         // implement a <block> construct
        return;
    }
    
    
    $parser->setHook ("amsmath", function  ($in, $ar, $parser, $frame)  { return  TeXProcessor::lazyRender ($in, $ar, "amsmath", $parser);} );
    //foreach (TAGS as $key => $tag) { $parser->setHook ($tag, function  ($in, $ar, $parser, $frame) use ($tag) { return  TeXProcessor::lazyRender ($in, $ar, $tag, $parser);} ); } // TODO !!
    $parser->setHook ( 'block',            function ($in, $ar, $parser, $frame) { return  Parsifal::block ($in, $ar);}    );         // implement a <block> construct
  }


/////// TODO: Add a purge and rebuild - OR MAYBE NOT ?  build system that we do not need it 
  // THIS thing builds the buttons on the tab line of the vector template
  public static function onSkinTemplateNavigation( $skin, &$actions ) {
    global $wgTitle, $wgUser, $wgEmailPageGroup;
    if( is_object( $wgTitle ) && $wgUser->isLoggedIn()  ) {
      //  $actions['views']['email'] = array( 'text' => wfMessage( 'email' )->text(), 'class' => false, 'href' => $url );  // show on main line CHC
      //$actions['actions']['purge'] = array(  'class' => false, 'href' => "" );  // show under "more" button  CHC
      $actions['actions']['rebuild']  = array(  'text'=> "Rebuild",   'class' => false, 'href' => "" );  // show under "more" button  CHC        // href can be a javascript: URL
      $actions['actions']['Fullpage'] = array(  'text' => "FullPage", 'title' => 'Download a Latex version', 'class' => false, 'href' => $wgTitle->getLocalURL ('action=fullpage') );  // show under "more" button  CHC        // href can be a javascript: URL      

  
                     
    }
    return true;
  }
  
  
  // CAVE: handle name in mediawiki will be deprecated to a new version soon .....
  public static function onPageSaveComplete ($wikiPage, $user, $mainContent, $summaryText, $isMinor, $isWatch, $section, $flags, $revision, $status, $originalRevId, $undidRevId ) {
    TeXProcessor::debugLog( "Parsifal::onPageSaveComplete entered \n");
    TeXProcessor::debugLog( "Parsifal::onPageSaveComplete title=". $wikiPage->getTitle()." namespace=" . $wikiPage->getTitle()->getNamespace() . " \n");
      
    if ($wikiPage->getTitle()->getNamespace() == NS_MAIN) {TeXProcessor::createNewPage();}
    // do not create new page in other namespaces (especially not for HELP, since otherwise we have a loop


  //  Parsifal::cleanupParsifalCache ();

  }
  
  
  
  static public function block       ($in, $ar)   {return "<div style='width:600px;'>" . $in . "</div>";}

  // this provides an early insert into the body 
  public static function onOutputPageBeforeHTML( OutputPage &$out, &$text ) {
    global $wgExtensionAssetsPath;
    $out->addJSConfigVars ( 'Parsifal', self::prepareJSConfig() );         
    $out->addModuleStyles ( ["ext.myExtensionBundleCHC"] );  // must addModuleStyles separately in order to prevent FOUC 
    $out->addHtml ("<script data-debug='marker' src='" . $wgExtensionAssetsPath . "/Parsifal/js/helper.js'></script>");        // this is for early injection 
    // CAVE: even when we add js/helper.js to extension.json and do an $out->addModules it is NOT properly picked up as it should - so we do this here as above 
  }
  
  // this provides an even earlier injection, we currently do not use it but keep it in the code
  public static function onBeforePageDisplay ( OutputPage $out ) {
    global $wgExtensionAssetsPath;
    // $out->addHeadItem ( 'myUniqueName', Html::inlineScript( "console.info ('-------------------------Parsifal.php extension of Mediawiki: could insert code here');" ) );
    //    $out->addHeadItem ( 'myUniqueName2', Html::inlineScript( "console.info ('Parsifal.php extension of Mediawiki: could insert code here part 2');" ) );    
    // TODO: Might need a getCSP()->getNonce() after Version 1.35  
    // TODO:  might need a  Html::inlineScript( "Put all your js here", $out->getCSPNonce() )  after 1.32
  }
  

  private static function prepareJSConfig () {  // returns the configuration variables to be exported to the Javascript portion of the extension
    global $wgServer, $wgScriptPath;
    $vars = [];                
    $vars['TAGS']     = TAGS;
    $vars['HTML_URL'] = $wgServer.$wgScriptPath.HTML_URL;
    $vars['JS_PATH']  = JS_PATH;
    return $vars;
  }

  // inject a section action for editing the section with code mirror
  public static function onSkinEditSectionLinks ( $skin, $title, $section, $tooltip, &$links, $lang ) {
    // die (print_r ($links, true));
    $links['parsifaledit'] = [
      'targetTitle' => $title,
      'text' => "edit CM",
       'attribs' => ["title" => "Edit with Code Mirror"],
       'query' => array( "action" => "edit", "section" => $section, "editormode" => "codemirror" ),
       'options' => array(),
    ];
  }


  // when we edit a page, intercept the edit process via javascript and insert an edit preview (if appropriate)
  public static function onEditPageshowEditForminitial ( EditPage &$editPage, OutputPage $output) {
     // the functionality of preventing Latex preview since we have no Latex on this page is done in editpreviewPatch() in helper.js Javascript
    $output->addJSConfigVars ( 'Parsifal', Parsifal::prepareJSConfig() );             // export configuration to HTML code for access via Javascript
    
    if (false) { // version without Codemirror
      $editPage->editFormTextAfterWarn = "<script>editPreviewPatch();</script>";  // immediately after display of edit UI patch it for TeX preview
    }
    else {       // version which also offers Codemirror as editor
      $myHtml  = "<script src='extensions/Parsifal/vendor/codemirror/codemirror-5.65.3/lib/codemirror.js'></script>";
      $myHtml .=  "<link rel='stylesheet' href='extensions/Parsifal/vendor/codemirror/codemirror-5.65.3/lib/codemirror.css'></script>";
      $myHtml .=  "<link rel='stylesheet' href='extensions/Parsifal/codemirror/codemirror-parsifal.css'></script>";      
      $myHtml .=  "<script src='extensions/Parsifal/vendor/codemirror/codemirror-5.65.3/mode/stex/stex.js'></script>";
      $myHtml .=  "<script src='extensions/Parsifal/vendor/codemirror/codemirror-5.65.3/addon/edit/matchbrackets.js'></script>";      
      $myHtml .=  "<script>editPreviewPatch();</script>";
      $editPage->editFormTextAfterWarn = $myHtml;
    }
    
  }
  
  
  // this hook is used to ensure that after editing an included template in the MediaWiki namespace the file is copied back to the file system
  // this is necessary since the templates must be picked up from the file system due to the manner how the preview endpoint is constructed
  public static function afterAttemptSave (EditPage $editPage, Status $status, $details) {
    $VERBOSE = true;
    $TEMPLATE_PATH = TEMPLATE_PATH; $LATEX_FORMAT_PATH = LATEX_FORMAT_PATH;   $PDFLATEX_FORMAT_PATH = PDFLATEX_FORMAT_PATH;
    if ($VERBOSE) {TeXProcessor::debugLog( "Parsifal::afterAttemptSave called, title is namespace is ".$editPage->getTitle()->getNamespace()." \n");}

    if ( $editPage->getTitle()->getNamespace() == NS_MEDIAWIKI) {  // only if the edit takes place in the MediaWiki namespace
      $titleText = $editPage->getTitle()->getText();
      if ( str_starts_with($titleText, "ParsifalTemplate/") ) {
        $shortName = str_replace ("ParsifalTemplate/", "", $titleText);
        
        if ($VERBOSE) {TeXProcessor::debugLog("Parsifal::afterAttemptSave: copying MediaWiki:ParsifalTemplate/$titleText to $shortName.tex \n");}
        
         
          $myTitle   = Title::newFromText( $titleText, NS_MEDIAWIKI );
          $myArticle = new Article( $myTitle );
          $template  = $myArticle->getPage()->getContent()->getNativeData();  // the data in MediaWiki:ParsifalTemplate/$shortName
          
          $templateFileName = "$TEMPLATE_PATH$shortName.tex";
          if ($VERBOSE) {TeXProcessor::debugLog("Parsifal::afterAttemptSave: copying MediaWiki:ParsifalTemplate/$titleText to $templateFileName \n");}          
          file_put_contents( $templateFileName, $template, LOCK_EX); 
          
          // remove everything beginning with \begin{document}...   and place the rest as source into the format directories
          $index = strpos ($template, "\begin{document}");
          $templatePrecompileSrc = substr ($template, 0, $index);
          
          $templatePrecompileSrc = preg_replace ( '/(^<pre>\s*$)|(<^\/pre>\s*$)/m', "", $templatePrecompileSrc );   // remove <pre> ... </pre> which improves display of page
          $templatePrecompileSrc = Parsifal::injectTemplate ($templatePrecompileSrc);
          
          file_put_contents( "$LATEX_FORMAT_PATH$shortName.tex", $templatePrecompileSrc, LOCK_EX);    
          file_put_contents( "$PDFLATEX_FORMAT_PATH$shortName.tex", $templatePrecompileSrc, LOCK_EX);        
          
          TeXProcessor::cleanUpAll ();                                       // cleanup all files since a fresh template requires regeneration
          // TODO: A---------------------------- need to register dependencies on the templates 
 
          if ($VERBOSE) {TeXProcessor::debugLog("Parsifal::afterAttemptSave: will now invoke precompiler on $shortName \n");}          
          $inError = TeXProcessor::precompile ($shortName);
          
          if ($inError == false) {  // no precompilation error
            if ($VERBOSE) {TeXProcessor::debugLog("Parsifal::afterAttemptSave: completed precompilation successfully on $shortName \n");}             
          }
          else {
            throw new Exception ("Error when compiling the Template. Reverting the change. Full error is: " . $inError);
          }
          if ($VERBOSE) {TeXProcessor::debugLog("Parsifal::afterAttemptSave completed on $titleText\n");}
       
      }
    }
    
  
    
  }  // end function afterAttemptSave
    
  // take the string contents $text and replace all lines of the form Mediawiki:ParsifalInclude/<name> by the content of this file
  private static function injectTemplate ($text) {
    $regExp = "/^MediaWiki:ParsifalInclude\/[a-zA-Z0-9\-_\/]+/m";
    preg_match_all ($regExp, $text, $matches);
    $found = $matches[0];  // array of all matches found
    // die (print_r ( $found, true));
    foreach ($found as $titleText) {
      $myTitle   = Title::newFromText( $titleText, NS_MEDIAWIKI );
      $myArticle = new Article( $myTitle );
      if ($myArticle->getPage()->getContent() == null) {
        throw new Exception ("Could not find include file " . $titleText. " *** Please make sure that this file exists! ");
      }
      $template  = $myArticle->getPage()->getContent()->getNativeData();  // the data in MediaWiki:ParsifalTemplate/$shortName
      $text = str_replace ($titleText, $template, $text);
    }
   return $text;
  }  
    
    
    
  public static function cleanupParsifalCache () {
    $VERBOSE = false;      
    TeXProcessor::debugLog ("\n\n cleanupParsifalCache called\n" );
    
    $set        = new Ds\Set();          // collect all the hashes we might see
    $extensions = new Ds\Set ();         // collect all the extensions we might see
    if (is_dir(CACHE_PATH)) {
      TeXProcessor::debugLog ("cleanupParsifalCache: CACHE_PATH is a directory \n" );
      if ( $dh = opendir(CACHE_PATH) ) {
        if ($VERBOSE) {TeXProcessor::debugLog ("cleanupParsifalCache: opened CACHE_PATH as a directory \n" );}
        while (($file = readdir($dh)) !== false) {
          if ($VERBOSE) {TeXProcessor::debugLog ("Parsifal::cleanupParsifalCache: sees: " . $file. " \n");}
          $fileHash = substr ($file, 0, 32);
          $ext = substr ($file, 32);
          if ( strlen ($ext > 0)      ) {$extensions->add ($ext); }
          if ( strlen ($fileHash) > 3 ) {$set->add ($fileHash);   }  // exclude directories . and .. from being added          
        }
//      TeXProcessor::debugLog ("cleanupParsifalCache: set obtained is: " .print_r ( $set, true). " \n" );  
      TeXProcessor::debugLog ("cleanupParsifalCache: set obtained contains elements: " . $set->count() . " \n" );        
      TeXProcessor::debugLog ("cleanupParsifalCache: closing CACHE_PATH as a directory \n" );  
      closedir($dh);
      }
      else {
        TeXProcessor::debugLog ("cleanupParsifalCache: could not open CACHE_PATH directory \n" );
      }
    }
    else {
      TeXProcessor::debugLog ("cleanupParsifalCache: CACHE_PATH is not a directory \n" );
    }
    
    // remove all used 
    $dbl = MediaWikiServices::getInstance()->getDBLoadBalancer();
    $dbr = $dbl->getConnectionRef ( DB_REPLICA );
    
    /* starting from 1.35
    $res = $dbr->newSelectQueryBuilder()
      ->select ( [ 'pp_value' ] )
      ->from   ( 'page_props' )
      ->where  ( ["pp_propname" => 'ParsifalHashsUsed'] )
      ->caller( __METHOD__ )
      ->fetchResultSet();     
    TeXProcessor::debugLog ( "cleanupParsifalCache: iterating result set \n");
    */
    
    // before 1.35
    $res = $dbr->select('page_props', [ 'pp_value' ],  'pp_propname ="ParsifalHashsUsed"',  __METHOD__, [ ] );
    
    foreach ( $res as $row ) {
      $hashArray = unserialize ($row->pp_value);
      foreach ($hashArray as $item) {
        TeXProcessor::debugLog ( "cleanupParsifalCache: removing used hash: " . $item . " \n") ;
        $set->remove ($item);
      } 
    }
    TeXProcessor::debugLog ("cleanupParsifalCache: set obtained, after removal, contains elements: " . $set->count() . " \n" );        
    
    foreach ( $set as $elem ) {
      TeXProcessor::debugLog ("removing for hash " . $elem . " \n");
      foreach ( $extensions as $ext) {
           TeXProcessor::debugLog ("unlinking " . $elem . $ext . " \n");
        unlink ($elem . $ext);
        
      }
    }
    
//    $propValue = $dbr->selectField( 'page_props', 'pp_value', [ 'pp_propname' => "ParsifalHashsUsed" ], __METHOD__ );
//    $propValue = unserialize ($propValue);  
//    $initialHashsUsed = $propValue;
//    if ($VERBOSE) {TeXProcessor::debugLog ("Parsifal::onParserFirstCallInit sees ParsifalHashsUsed: " . print_r ($initialHashsUsed, true). " \n");}      
  
  } // end function  
    
    
    
    
} // end class
    

/*  // idea how to fix cross tag references and labels as well as equation numbers

    // for all saves which are not in MEDIAWIKI name space: get the converted html content
    $title = $editPage->getTitle();
    $content = $editPage->getArticle()->getPage()->getContent()->getParserOutput($title)->getText();
    
    // search for data-hashvaluepickup and obtain an array of all hashes of TeX files which are used on this specific page
    preg_match_all ('/data-hashvaluepickup="([a-zA-Z0-9\-]*)"/', $content, $matches); 
    $inter = $matches[1];
    TeXProcessor::debugLog( "######################## afterAttemptSave: " . json_encode ($inter). "\n");
    
    // and now have a latex run go over the sequence which we 
    
*/    
  


function formatException ($e) {return "<h3>A processing exception occured in file " . $e->getFile() . " in line " . $e->getLine() . "</h3>".htmlspecialchars ($e->getMessage()); }

?>