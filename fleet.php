<?php
$start_time = microtime(true);
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
    if (!$fleet->update() || $fleet->getError()) {
      $page->setError($fleet->getMessage());
      $page->display();
    }
  }
} else {
  $fleet = new ESIFLEET($_SESSION['fleetID'], $_SESSION['characterID']);
  if ($fleet->getError()) {
    $page->setError($fleet->getMessage());
    $page->display();
    exit;  
  }
  if ($fleet) {
    if (!$fleet->update() || $fleet->getError()) {
      $page->setError($fleet->getMessage());
      $page->display();
      exit;
    }
  }
}

function getFleetTable($fleet) {
    $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
    $members = $fleet->getMembers();
    $locationDict = EVEHELPERS::getSystemNames(array_column($members, 'system'));
    $shipDict = EVEHELPERS::getInvNames(array_column($members, 'ship'));
    $table = '<table id="fleettable" class="table table-striped table-hover" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Pilot</th>
          <th>Location</th>
          <th>Ship</th>
          <th class="no-sort">Fit</th>
          <th class="no-sort">backupfc</th>
        </tr>
      </thead>';
      foreach ($members as $m) {
          $table .='<tr id='.$m['id'].'>';
          $table .='<td>'.$m['name'].'</td><td>'.$locationDict[$m['system']].'</td><td>'.$shipDict[$m['ship']].'</td><td>'.$m['fit'].'</td><td><input type="checkbox" value="" '.(($m['backupfc']) ? 'checked ':'').'onchange="backupfc(this)"></td>';
          $table .='</tr>';
      }
      $table .='<tbody>
      </tbody>
    </table>
    <script>
        function backupfc(cb) {
            var id = $(cb).closest("tr").attr("id");
            var state = cb.checked;
            $.ajax({
                type: "POST",
                url: "'.URL::url_path().'aj_backupfc.php",
                data: {"fid" : '.$fleet->getFleetID().', "cid" : id, "ajtok" : "'.$_SESSION['ajtoken'].'", "state" : state},
                success:function(data)
                {
                  if (data !== "true") {
                      alert("something went wrong");
                  }
                }
                }); 
        }
    </script>';
    return $table;
}
$page->addFooter('<script>$(document).ready(function() {
            $("#fleettable").dataTable(
               {
                   "bPaginate": false,
                   "aoColumnDefs" : [ {
                       "bSortable" : false,
                       "aTargets" : [ "no-sort" ]
                   } ],
               });
        });
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/dataTables.bootstrap.min.js"></script>');

$page->addBody(getFleetTable($fleet));
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;
?>
