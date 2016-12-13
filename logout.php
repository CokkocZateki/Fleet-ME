<?php

require_once('config.php');
require_once('loadclasses.php');

unset($_SESSION['characterID']);
unset($_SESSION['characterName']);
session_destroy();

$page = new Page('SSO Login');
$page->setInfo("You were logged out.");
$page->display();
exit;
?>
