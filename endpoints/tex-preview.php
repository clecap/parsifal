<?php

/* a cludge function which connects the tex-preview.php endpoint back to the TexProcessor */

// need the following two lines to obtain reasonable errors from the endpoint instead of only 500 er status from webserver
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once ("../config.php");
require_once ("../php/TexProcessor.php");

TexProcessor::texPreviewEndpoint();
   
?>
