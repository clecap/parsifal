<?php

require_once (  dirname(__FILE__) . "/../config.php");    // include path configuration

class ParsifalReset extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalReset', 'resetParsifal' ); }
  
  public function getGroupName() {return 'other';}
  
  public function onSubmit( array $data )  {
    // if ( !$this->getAuthority()->isAllowed( 'resetParsifal' ) ) {return false;}
    if (! $this->getUser()->isAllowed ("resetParsifal") ) {return false;}
    $dir = new DirectoryIterator( CACHE_PATH );
    foreach ($dir as $fileinfo) { 
      if (!$fileinfo->isDot()) {unlink ( CACHE_PATH . $fileinfo->getFilename());}
    }    
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	$output->addHTML( '<b>Clicking submit will clear the Parsifal cache and log file</b>' );
    return array();
  }

  
}
