<?php

// this file realizes the Parsifal Special Page Resetting the entire system

require_once (  dirname(__FILE__) . "/../config/config.php");    // include path configuration

class ParsifalReset extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalReset', 'resetParsifal' ); }
  
  public function getGroupName() {return 'dante';}
  
  public function onSubmit( array $data )  {
    $VERBOSE = false;  // CAVE: if we debug this and set this to true we MUST comment away the deletion of LOGFILE below or we will not see what we log !!
    
 
    global $IP;
    if (! $this->getUser()->isAllowed ("resetParsifal") ) {return false;}                                       // check for permission "resetParsifal" 
    $dir = new DirectoryIterator( CACHE_PATH );                                                                 // iterate the CACHE_PATH
    
    if ($VERBOSE) danteLog ("Parsifal", "Log was called\n");

    // CAVE: These are mass deletions and in the development machine without protection by containers or different users
    // THUS: Make sure we only delete stuff which we really want to delete and be rather conservative on the diverse checks since a typo somewhere else might produce desasters :-(
    foreach ($dir as $fileinfo) {
      
      if ( !$fileinfo->isDot() && $fileinfo->isFile() && file_exists (CACHE_PATH . $fileinfo->getFilename()) && str_contains ($fileinfo->getFilename(), "_pc_pdflatex") ) 
        {  unlink ( CACHE_PATH . $fileinfo->getFilename());  }  // unlink files
      

      if ( !$fileinfo->isDot() && $fileinfo->isDir() && file_exists (CACHE_PATH . $fileinfo->getFilename()) && str_starts_with ($fileinfo->getFilename(), "_minted-") ) {
        // this is a directory, probably of files
        // first iterate the diretory for files, then clear directory itself
        $sub = new DirectoryIterator ( CACHE_PATH.$fileinfo->getFilename() );
        foreach ($sub as $subfile) {
          if (  $subfile->isDot() )    { if ($VERBOSE) {danteLog ("Parsifal", "Skipping sub isDot\n");}  continue; }  
          if ( !$subfile->isFile() )  { if ($VERBOSE) {danteLog ("Parsifal", "Skipping not file " .$sub->getFilename(). "\n");}  continue; }  
          if (  !file_exists ( CACHE_PATH . $fileinfo->getFilename(). "/". $subfile->getFilename()) ) { if ($VERBOSE) { danteLog ("Parsifal", "Skipping not exist: "  . CACHE_PATH . $fileinfo->getFilename(). "/". $subfile->getFilename() . "\n");} continue; } 

           $ret = unlink (CACHE_PATH . $fileinfo->getFilename() . "/" . $subfile->getFilename()); 
          if ($VERBOSE) danteLog  ("Parsifal", "UNLINKING: ". CACHE_PATH . $fileinfo->getFilename() . "/" . $subfile->getFilename(). "    " . ($ret ? "SUCCEEDED" : "FAILED") . "\n" );

        } // end inner foreach
        if ( !$fileinfo->isDot() && $fileinfo->isDir() && file_exists (CACHE_PATH . $fileinfo->getFilename()) && str_starts_with ($fileinfo->getFilename(), "_minted-") )   
          { $ret = rmdir ( CACHE_PATH. $fileinfo->getFilename() ); 
            if ($VERBOSE) danteLog ("Parsifal", "RMDIR: " . CACHE_PATH. $fileinfo->getFilename() . "   " . ( $ret ? "SUCCEEDED" : "FAILED") . "\n");
          }
         else {
           if ($VERBOSE) danteLog ("Parsifal", "RM SKIPPED: " . CACHE_PATH . $fileinfo->getFilename() . "\n" );
         }   
      }  // end if
    }  // end outer foreach
    
    if ( file_exists ( LOG_PATH ) ) {unlink ( LOG_PATH );}                                                      // unlink the main log (only if it exists - to prevent any error messages from being displayed)

    // names of other files to be removed
    $otherFiles = array ( "DantePresentations/endpoints/ENDPOINT_LOG",  "DantePresentations/LOGFILE",  "DanteLinks/DANTELINKS-LOGFILE",  "DanteTree/LOGFILE");
    foreach ($otherFiles as &$fileName) {
      $full =  $IP. "/extensions/" . $fileName;      
      if ( file_exists ($full) ) {unlink ($full);}
    }


    touch($IP."/LocalSettings.php");
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	//$output->addHTML( 'Clicking submit will clear the Parsifal cache and log file and clear the page and parser caches' );
    return array();
  }

  
}
