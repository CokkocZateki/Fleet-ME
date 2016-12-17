<?php
$start_time = microtime(true);
require_once('config.php');
require_once('loadclasses.php');
require_once('auth.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

$page = new Page('Ship fitting');

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

$fitting = FITTING::getCharFit($_SESSION['characterID']);
$html .= '<div class="row">';

if ($fitting) {
  $names = (FITTING::getNames($fitting));
  $page->addHeader('<link rel="stylesheet" href="css/fitting.css" type="text/css" />');
  $html .= '<div class="fitting col-xs-12 col-md-6 col-lg-4">
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#fit">Fitting</a></li>
    <li><a data-toggle="tab" href="#export">Text based</a></li>
  </ul>
  <div class="tab-content">
  
  <div id="fit" class="tab-pane fade in active">
  <h5>'.$names[$fitting['ship']].'</h5>
  <div class="fitting-container">
  <img class="fitting-render" src=https://imageserver.eveonline.com/Render/'.$fitting['ship'].'_256.png>
  <img class="fitting-overlay" src=img/fittingbase_256.png>';
  foreach ($fitting['highs'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-high-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['meds'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-med-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['lows'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-low-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['rigs'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-rig-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['subsys'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-sub-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  $html .= '</div></div>
  <div id="export" class="tab-pane fade">
  <blockquote class="small">
  ['.$names[$fitting['ship']].',Fleet-yo fit]<br/>';
  foreach ($fitting['lows'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['meds'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['highs'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['rigs'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['subsys'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['drones'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '</blockquote></div></div></div>';
}

$html .= '<div class="col-xs-12 col-md-6 col-lg-4"><form action="" method=post>
  <div class="form-group col-xs-12">
    <div class="row"><textarea class="form-control" rows="15" name="fitting" id="fitting" placeholder="Paste your fitting"></textarea><br/></div>
    <div class="row"><button type="submit" class="btn btn-primary">Submit</button></div>
  </div>
</form></div></div>';

$page->addBody($html);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;


?>
