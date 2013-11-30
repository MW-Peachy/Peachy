<?php
    //This file is the default configuration file for Peachy.  Please do not modify it.  Please create a file called config.local.inc.php with the variables you want altered.
    
    //Controls weather or not the peachy tag is displayed at the end of edit summaries.
    $notag = false;
    $tag = " ([[en:WP:PEACHY|Peachy ".PEACHYVERSION."]])";
    
    //For debugging purposes.  This generates enourmous amounts of data.  Switch off if your space is limited.
    $logGetCommunicationData = true;
    $logPostCommunicationData = true;
    $logCommunicationData = true;
    $logSuccessfulCommunicationData = true;
    $logFailedCommunicationData = true;
    
    //Bot output
    $displayPostOutData = true;
    $displayGetOutData = true;
    $displayPechoVerbose = false; //Major security risk if set to true.  Switch to true at your own risk.
    $displayPechoNormal = true;
    $displayPechoNotice = true;
    $displayPechoWarn = true;
    $displayPechoError = true;
    $displayPechoFatal = true;
    $webOutput = false; //Switch to true if you are using a webserver to output your data instead of commandline.
    
    //This controls bot checks, before doing an action.  This change will affect every bot using it.  Enabling it drops the edit rate.
    $disablechecks = false;
    
    //Sometimes a site certificate can't be verified causing the connection to be terminated.  Set this to false, if this is the case.  Do so at your own risk.
    $verifyssl = true;
    
    //Disable automatic updates.  You will need to download updates manually then, until it is re-enabled again.
    $disableUpdates = false;
    
    //If your bots run on the Wikimedia Foundation Labs, this will allow Peachy to accomodate some it's features to work with the labs environment.
    $useLabs = false;
    
    //API Communication settings.
    $killonfailure = true;
    $maxattempts = 20;
    
    //Global bot settings
    $masterrunpage = null;
    
    //Import local settings if available
    if( file_exists($pgIP.'config.local.inc.php') ) require_once( $pgIP.'config.local.inc.php' );
    
?>
