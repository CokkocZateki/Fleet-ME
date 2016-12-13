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
$page->addBody('<img src="https://imageserver.eveonline.com/Character/'.$esipilot->getCharacterID().'_64.jpg">');
$page->display();
exit;
?>
