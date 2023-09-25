<?php

class ParsifalPreferences {

public static function onUserSaveSettings( User $user ) {}

public static function onGetPreferences ( $user, &$preferences ) { 

    $preferences['style']       = ['type' => 'text',  'section' => 'parsifal/parsifal-style', 'label-message' => 'parsifal-label-key',     'help-message' => 'parsifal-help-style'];
   
}

}