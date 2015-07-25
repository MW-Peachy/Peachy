<?php
/**
 * This file is the default configuration file for Peachy. Please do not modify it. Please create a file called
 *      config.local.inc.php with the variables you want altered.
 */

//Controls whether or not the peachy tag is displayed at the end of edit summaries.
$pgNotag = false;
$pgTag = " ([[en:WP:PEACHY|Peachy " . PEACHYVERSION . "]])";

//For debugging purposes. This generates enourmous amounts of data. Switch off if your space is limited.
$pgLogGetCommunicationData = true;
$pgLogPostCommunicationData = true;
$pgLogCommunicationData = true;
$pgLogSuccessfulCommunicationData = true;
$pgLogFailedCommunicationData = true;

//Bot output
$pgDisplayPostOutData = true;
$pgDisplayGetOutData = true;
$pgDisplayPechoVerbose = false; //Major security risk if set to true. Switch to true at your own risk.
$pgDisplayPechoNormal = true;
$pgDisplayPechoNotice = true;
$pgDisplayPechoWarn = true;
$pgDisplayPechoError = true;
$pgDisplayPechoFatal = true;
$pgWebOutput = false; //Switch to true if you are using a webserver to output your data instead of commandline.

//This controls bot checks, before doing an action. This change will affect every bot using it. Enabling it drops the edit rate.
$pgDisablechecks = false;

//Sometimes a site certificate can't be verified causing the connection to be terminated. Set this to false, if this is the case. Do so at your own risk.
$pgVerifyssl = true;

//Disable automatic updates. You will need to download updates manually then, until it is re-enabled again.
$pgDisableUpdates = false;
$pgExperimentalupdates = false;

//If your bots run on the Wikimedia Foundation Labs, this will allow Peachy to accomodate some it's features to work with the labs environment.
$pgUseLabs = false;

//API Communication settings.
$pgThrowExceptions = true;
$pgMaxAttempts = 20;

//Global bot settings
$pgMasterrunpage = null;

//SSH Settings - Should Peachy SSH into a server as it initializes?
$pgUseSSH = false;
$pgHost = null;
$pgPort = 22;
$pgUsername = null;
$pgPassphrase = null; //Passphrase to decrypt key file for authentication or password to authenticate with to server
$pgPrikey = null;     //File path to the private key file.
$pgProtocol = 2;      //SSH protocol to use. 1=SSH1 or 2=SSH2.
$pgTimeout = 10;

//Import local settings if available
if (file_exists($pgIP . 'config.local.inc.php')) {
    require_once($pgIP . 'config.local.inc.php');
}
