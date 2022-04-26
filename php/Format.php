<?php

require_once (  dirname(__FILE__) . "/../config.php");    // include path configuration

class ParsifalFormat extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalFormat' ); }
  
  public function getGroupName() {return 'other';}
  
  public function onSubmit( array $data )  {
  }

  public function getFormFields()  {
    $output = $this->getOutput();
   	$output->addHTML( '<b>Clicking submit will format .....  DRAFT</b>' );
    return array();
  }

}
