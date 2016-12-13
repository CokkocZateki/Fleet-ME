<?php

require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

if (isset($_GET['code'])) {
  if (isset($_SESSION['characterID'])) {
      $page = new Page('SSO Login');
      $html = "Error: Code was used already.";
      $page->setError($html);
      $page->display();
      exit;
  }
  $code = $_GET['code'];
  $state = $_GET['state'];
  if ($state != $_SESSION['authstate']) {
    $page = new Page('SSO Login');
    $html = "Error: Invalid state, aborting.";
    session_destroy();
    $page->setError($html);
    $page->display();
    exit;
  }
  $esisso = new ESISSO();
  $esisso->setCode($code);
  if (!$esisso->getError()) {
    $result = $esisso->addToDb();
    if ($result) {
        $_SESSION['characterID'] = $esisso->getCharacterID();
        $_SESSION['characterName'] = $esisso->getCharacterName();
        $page = new Page('SSO Login');
        $page->setInfo($esisso->getMessage());
        $page->display();
        exit;
    }
  } else {
    $page = new Page('SSO Login');
    $page->setError($esisso->getMessage());
    $page->display();
    exit;
  }
}

if (isset($_GET['login'])) {
  if ($_GET['login'] == 'fc') {
    if (!isset($_GET['fleetlink'])) {
        $html = '<form action="" method="get">
                   <div class="form-group row">
                     <input type="hidden" name="login" value="fc">
                     <label for="fleetlink" class="col-xs-1 col-form-label">Fleet link:</label>
                     <div class="col-xs-6">
                       <input class="form-control" type="text" id="fleetlink" name="fleetlink">
                     </div>
                     <p id="passwordHelpBlock" class="form-text text-muted">Paste your external fleet link.</p>
                   </div>
                   <button type="submit" class="btn btn-primary">Submit</button>
                 </form>';
        $page = new Page('SSO Login');
        $page->setInfo($html);
        $page->display();
        exit;
    }
    preg_match('/(?<=fleets\/)([0-9]*)/', $_GET['fleetlink'], $fleetmatch);
    if (!count($fleetmatch)) {
        $page = new Page('SSO Login');
        $page->setError('No valid fleet link.');
        $page->display();
        exit;
    }
    $fleetid = $fleetmatch[0];
    $_SESSION['fleetID'] = $fleetid; 
    $scopes = array('esi-location.read_location.v1',
                    'esi-location.read_ship_type.v1',
                    'esi-universe.read_structures.v1',
                    'esi-ui.write_waypoint.v1',
                    'esi-fleets.read_fleet.v1',
                    'esi-fleets.write_fleet.v1');
  } elseif ($_GET['login'] == 'member') {
    $scopes = array('esi-location.read_location.v1', 
                    'esi-location.read_ship_type.v1', 
                    'esi-universe.read_structures.v1',
                    'esi-ui.write_waypoint.v1');
  }
  $authurl = "https://login.eveonline.com/oauth/authorize/";
  $state = random_str(32);
  $_SESSION['authstate'] = $state;
  $url = $authurl."?response_type=code&redirect_uri=".URL::full_url()."&client_id=".ESI_ID."&scope=".implode(' ',$scopes)."&state=".$state;
  header('Location: '.$url);
  exit;
}
?>
