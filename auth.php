<?php
require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

if (!isset($_SESSION['characterID'])) {
  $authtoken = AUTHTOKEN::getFromCookie();
  if ($authtoken) {
    if ($authtoken->verify()) {
      $_SESSION['characterID'] = $authtoken->getCharacterID();
    }
  }
}

if (isset($_SESSION['characterID']) && !isset($_SESSION['characterName'])) {
  $esipilot = new ESIPILOT($_SESSION['characterID']);
  $_SESSION['characterName'] = $esipilot->getCharacterName();
}

?>
