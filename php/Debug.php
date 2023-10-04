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
     
    echo "FOUND: ".print_r ($data, true);
    if ($data["radio"] == 0) { echo "IS NULL";
      copy ("$IP/DanteSettings-production.php", "$IP/DanteSettings-used.php");
    }
    if ($data["radio"] == 1) { echo "IS ONE"; 
      copy ("$IP/DanteSettings-development.php", "$IP/DanteSettings-used.php");
    }
    echo "AFTER";

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
      // The options available within the checkboxes (displayed => value)
          'options' => [
              'Production' => 0,
              'Development' => 1
             // 'Option 2' => 'option2id',
          ],
      // The options selected by default (identified by value)
          'default' => 1,
      ]
  ];
  
    return $formDescriptor;




  }

  
}
