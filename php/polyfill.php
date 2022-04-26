<?php

// polyfill for missing str_starts_with in PHP with version less than 8, see https://www.php.net/manual/en/function.str-starts-with.php
if (!function_exists('str_starts_with')) { function str_starts_with($haystack, $needle) {return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0; } }
if (!function_exists('str_ends_with'))   { function str_ends_with($haystack, $needle) {return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;} }
if (!function_exists('str_contains'))    { function str_contains($haystack, $needle) {return $needle !== '' && mb_strpos($haystack, $needle) !== false; } }

/*
function LOG ($text) {
  if($tmpFile = fopen( LOG_PATH, 'a')) {fwrite($tmpFile, $text);  fclose($tmpFile);} 
  else {throw new Exception ("LOG in polyfill.php could not log"); }
}*/

function ASSERT_FILE ($name) {
  if (!file_exists ($name)) {
//    LOG ("ASSERT ERROR: Could not find file $name\n");
    throw new Exception ("ASSERT ERROR: Could not find file $name");
  }
  
}


?>
