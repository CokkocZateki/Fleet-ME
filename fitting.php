<?php
$start_time = microtime(true);
require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

$page = new Page('Character Info');

if (!isset($_SESSION['characterID'])) {
  $page->setError("You are not logged in.");
  $page->display();
  exit;
}

$html = '';

if (isset($_POST['fitting'])) {
    $fit = new FITTING($_POST['fitting']);
    $esipilot = new ESIPILOT($_SESSION['characterID']);
    if ($esipilot->getError()) {
      $page->setError($esipilot->getMessage());
    } else {
      if ($fit->addToChar($_SESSION['characterID'])) {
        $page->setInfo($fit->getMessage());
      } elseif ($fit->getError()) {
        $page->setError($fit->getMessage());
      }
    }
}

$html .= '<div class="row"><div class="col-xs-12"><form action="" method=post>
  <div class="form-group col-xs-12 col-sm-6">
    <div class="row"><label for="fitting">Paste your fitting:</label>
    <textarea class="form-control" rows="15" name="fitting" id="fitting"></textarea><br/></div>
    <div class="row"><button type="submit" class="btn btn-primary">Submit</button></div>
  </div>
</form></div></div>';

$page->addBody($html);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;


?>
