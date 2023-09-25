<?php

require_once (  dirname(__FILE__) . "/../config/config.php");    // include path configuration

class ParsifalFormat extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalFormat' ); }
  
  public function getGroupName() {return 'dante';}
  
  public function onSubmit( array $data )  {
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	$output->addHTML( '<b>Clicking submit will format .....  DRAFT</b>' );
    return array();
  }

}
