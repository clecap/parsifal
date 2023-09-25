<?php

// this file realizes the Parsifal Special Page Resetting the entire system

require_once (  dirname(__FILE__) . "/../config/config.php");    // include path configuration

class ParsifalReset extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalReset', 'resetParsifal' ); }
  
  public function getGroupName() {return 'dante';}
  
  public function onSubmit( array $data )  {
    global $IP;
    if (! $this->getUser()->isAllowed ("resetParsifal") ) {return false;}                                       // check for permission "resetParsifal" 
    $dir = new DirectoryIterator( CACHE_PATH );                                                                 // iterate the CACHE_PATH
    foreach ($dir as $fileinfo) {
      if (!$fileinfo->isDot() && file_exists (CACHE_PATH . $fileinfo->getFilename())) {unlink ( CACHE_PATH . $fileinfo->getFilename());} 
    }  // and unlink all contained files
    if ( file_exists ( LOG_PATH ) ) {unlink ( LOG_PATH );}                                                      // unlink the main log (only if it exists - to prevent any error messages from being displayed)
    touch($IP."/LocalSettings.php");
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	//$output->addHTML( 'Clicking submit will clear the Parsifal cache and log file and clear the page and parser caches' );
    return array();
  }

  
}
