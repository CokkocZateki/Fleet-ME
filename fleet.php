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
    $page->setWarning("Could not find a fleet for ".$_SESSION['characterName']);
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

if ($_SESSION['characterID'] == $fleet->getBoss()) {
   $page->addBody(getFleetHeader(true));
} elseif ($_SESSION['characterID'] == $fleet->getFC()) {
   $page->addBody(getFleetHeader(false));
}

if ($_SESSION['characterID'] == $fleet->getBoss() || $_SESSION['characterID'] == $fleet->getFC() || $fleet->isPublic()) {
    $page->addBody(getFleetTable($fleet));
    $page->addFooter(getScriptFooter());
}
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;

function getFleetHeader($fleet, $isBoss=false) {
    return;
}

function getFleetTable($fleet) {
    $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
    $members = $fleet->getMembers();
    $modcolumns = array_keys(FITTING::getModGroups());
    $locationDict = EVEHELPERS::getSystemNames(array_column($members, 'system'));
    $shipDict = EVEHELPERS::getInvNames(array_column($members, 'ship'));
    $shipTypeDict = EVEHELPERS::getInvGroupNames(array_column($members, 'ship'));
    $table = '<table id="fleettable" class="small table table-striped table-hover" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Pilot</th>
          <th>Location</th>
          <th>Ship</th>
          <th>Type</th>';
          foreach($modcolumns as $mc) {
              $table .='<th class="mod-header no-sort"><img class="mod-column" src="img/col_headers/'.$mc.'.png"></th>';
          }
          $table .='<th class="no-sort">backupfc</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <td align="right"><em>Total:</em></td>';
          foreach($modcolumns as $mc) {
                  $table .='<td align="center" style="font-weight: bold;"></td>';
              }
        $table .='</tr>  
      </tfoot>
      <tbody>';
      foreach ($members as $m) {
          $table .='<tr id='.$m['id'].'>';
          $table .='<td>'.$m['name'].'</td><td>'.$locationDict[$m['system']].'</td><td>'.$shipDict[$m['ship']].'</td><td>'.$shipTypeDict[$m['ship']].'</td>';
          if ($m['fit'] != null && $m['fit'] != '') {
              foreach(FITTING::getModGroups(json_decode($m['fit'], true)) as $mc) {
                 $table .='<td align="center">'.$mc.'</td>';
              }
          } else {
              foreach($modcolumns as $mc) {
                  $table .='<td></td>';
              } 
          }
          $table .='<td align="center"><input type="checkbox" value="" '.(($m['backupfc']) ? 'checked ':'').'onchange="backupfc(this)"></td>';
          $table .='</tr>';
      }
      $table .='</tbody>
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

function getScriptFooter() {
    $html = '<script>$(document).ready(function() {
            $("#fleettable").dataTable(
               {
                   "bPaginate": false,
                   "aoColumnDefs" : [ {
                       "bSortable" : false,
                       "aTargets" : [ "no-sort" ]
                   } ],
                   fixedHeader: {
                       header: true,
                       footer: true
                   },
                   "footerCallback": function (row, data, start, end, display) {
                       console.log("footer");
                       var api = this.api(), data;
                       var colNumber = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
                       var intVal = function (i) {
                             return typeof i === "string" ?
                                  i.replace(/[, Rs]|(\.\d{2})/g,"")* 1 :
                                  typeof i === "number" ?
                                  i : 0;
                       };
                       for (i = 0; i < colNumber.length; i++) {
                           var colNo = colNumber[i];
                           total2 = api
                               .column(colNo)
                               .data()
                               .reduce(function (a, b) {
                                   return intVal(a) + intVal(b);
                               }, 0);
                    
                           $(api.column(colNo).footer()).html(
                                total2
                           );
                       }
                    },
               });
        });
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/dataTables.bootstrap.min.js"></script>';
    return $html;
}

?>
