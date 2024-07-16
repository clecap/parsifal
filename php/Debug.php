<?php

// this file realizes the Parsifal Special Page Resetting the entire system

require_once (  dirname(__FILE__) . "/../config/config.php");    // include path configuration

class ParsifalDebug extends FormSpecialPage {

  public function __construct () {;
    parent::__construct( 'ParsifalDebug', 'resetParsifal' ); }
  
  public function getGroupName() {return 'dante';}
  
  public function onSubmit( array $data )  {
    global $IP;
    $VERBOSE = false;  // CAVE: if we debug this and set this to true we MUST comment away the deletion of LOGFILE below or we will not see what we log !!
    if ($data["radio"] == 0) { copy ("$IP/DanteSettings-production.php",          "$IP/DanteSettings-used.php");  }
    if ($data["radio"] == 1) { copy ("$IP/DanteSettings-development.php",         "$IP/DanteSettings-used.php");  }
    if ($data["radio"] == 2) { copy ("$IP/DanteSettings-development-deprec.php",  "$IP/DanteSettings-used.php");  }

    global $IP;
    if (! $this->getUser()->isAllowed ("resetParsifal") ) {return false;}                                       // check for permission "resetParsifal"   
    }

  public function getFormFields()  {
    $output = $this->getOutput();
   	$output->addHTML( 'Select operative mode' );
  
     $formDescriptor = [
      'radio' => [
          'type' => 'radio',
          'label' => 'Operative mode',
          'options' => [
              'Production' => 0,
              'Development' => 1,
              'Development & Deprecation' => 2
          ],
      // The options selected by default (identified by value)
          'default' => 1,
      ]
  ];
  
    return $formDescriptor;




  }

  
}
