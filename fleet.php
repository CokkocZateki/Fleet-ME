<?php
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');

$page = new Page('My fleet');

if (!isset($_SESSION['characterID'])) {
  $page->setError("You are not logged in.");
  $page->display();
  exit;
}


if(!isset($_SESSION['fleetID'])) {
  $fleet = ESIFLEET::getFleetForChar($_SESSION['characterID']);
  if (!$fleet) {
    $page->setError("Could not find a fleet");
    $page->display();
    exit;
  } else {
    $_SESSION['fleetID'] = $fleet->getFleetID();
  }
} else {
  $fleet = new ESIFLEET($_SESSION['fleetID'], $_SESSION['characterID']);
  if ($fleet->getError()) {
    $page->setError($fleet->getMessage());
    $page->display();
    exit;  
  }
}
$page->display();
exit;
?>
