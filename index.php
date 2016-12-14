<?php
require_once('loadclasses.php');
require_once('auth.php');

$page = new Page('Welcome.');
$page->addBody('This is going to be a fleet management/tracking tool once ESI fully supports it.<br/><br/>So long thanks for all the fish,<br/>Snitch');
$page->display();
?>
