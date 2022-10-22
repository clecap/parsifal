<?php

// this file realizes the Parsifal Special Page Resetting the entire system

require_once (  dirname(__FILE__) . "/../config.php");    // include path configuration

class ParsifalReset extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalReset', 'resetParsifal' ); }
  
  public function getGroupName() {return 'other';}
  
  public function onSubmit( array $data )  {
    if (! $this->getUser()->isAllowed ("resetParsifal") ) {return false;}                                       // check for permission "resetParsifal" 
    $dir = new DirectoryIterator( CACHE_PATH );                                                                 // iterate the CACHE_PATH
    foreach ($dir as $fileinfo) {if (!$fileinfo->isDot()) {unlink ( CACHE_PATH . $fileinfo->getFilename());} }  // and unlink all contained files
    if ( file_exists ( LOG_PATH ) ) {unlink ( LOG_PATH );}                                                      // unlink the main log (only if it exists - to prevent any error messages from being displayed)
    
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	$output->addHTML( '<b>Clicking submit will clear the Parsifal cache and log file</b>' );
    return array();
  }

  
}
