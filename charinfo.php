<?php

require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

if (!isset($_SESSION['characterID'])) {
  $page = new Page('Character Info');
  $page->setError("You are not logged in.");
  $page->display();
  exit;
}

$html = '';

$esipilot = new ESIPILOT($_SESSION['characterID']);
$page = new Page($esipilot->getCharacterName());
$page->addBody(' <div class="row">
  <div class="col-sm-1"><img class="img-rounded" src="https://imageserver.eveonline.com/Character/'.$esipilot->getCharacterID().'_64.jpg"></div>
  <div class="col-sm-3">Name: '.$esipilot->getCharacterName().'<br/></div>
  Name: '.$esipilot->getLocationName().'<br/>
  Name: '.$esipilot->getStationName().'<br/>
</div>');
$page->display();
exit;
?>
