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


$fleet = ESIFLEET::getFleetForChar($_SESSION['characterID']);
if (!$fleet) {
  if(isset($_SESSION['fleetID'])) {
    $fleet = new ESIFLEET($_SESSION['fleetID'], $_SESSION['characterID']);
    if ($fleet->getError()) {
      $page->setError($fleet->getMessage());
      $page->display();
      exit; 
    }
  } else {
    $page->setWarning("Could not find a fleet for ".$_SESSION['characterName']);
    $page->display();
    exit;
  }
} else {
  if (!$fleet->update() || $fleet->getError()) {
    if(isset($_SESSION['fleetID'])) {
      $fleet = new ESIFLEET($_SESSION['fleetID'], $_SESSION['characterID']);
      if ($fleet->getError()) {
        $page->setError($fleet->getMessage());
        $page->display();
        exit;
      } else {
        $fleet->update();
        if ($fleet->getError()) {
          $page->setError($fleet->getMessage());
          $page->display();
          exit;
        }
      }
    } else {
      $page->setError($fleet->getMessage());
      $page->display();
    }
  } else {
    $_SESSION['fleetID'] = $fleet->getFleetID();
  }
}

$page->addBody("<blockquote class='blockquote small'><p id='motd'>MOTD:<br/>".$fleet->getMotd()."</p></blockquote>");

$backupfc = false;
foreach ($fleet->getMembers() as $member) {
  if ($member['id'] == $_SESSION['characterID']) {
    $backupfc = $member['backupfc'];
  }
}

if ($_SESSION['characterID'] == $fleet->getBoss()) {
   $page->addBody(getFleetHeader($fleet, true));
} elseif ($_SESSION['characterID'] == $fleet->getFC()) {
   $page->addBody(getFleetHeader($fleet, false));
}

if ($_SESSION['characterID'] == $fleet->getBoss() || $_SESSION['characterID'] == $fleet->getFC()) {
    $page->addBody(getFleetTable($fleet, true));
    $page->addFooter(getScriptFooter());
} elseif ($fleet->isPublic() || $backupfc) {
    $page->addBody(getFleetTable($fleet, false));
    $page->addFooter(getScriptFooter());
}
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;

function getFleetHeader($fleet, $isBoss=false) {
    if (!isset($_SESSION['ajtoken'])) {
        $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
    }
    $motd = $fleet->getMotd();
    $fleetlink = URL::url_path().'fitting.php';
    $fh = '<h5>Fleet options</h5>
           <div class="row">
             <div class="col-xs-12 col-md-4 col-lg-2"><div class="checkbox"><label><input type="checkbox" value="" '.($fleet->isPublic() ? 'checked ':'').'onchange="setpublic(this)">Composition public</label></div></div>
             <div class="col-xs-12 col-md-4 col-lg-2"><div class="checkbox"><label><input type="checkbox" value="" '.($fleet->getFreemove() ? 'checked ':'').'onchange="setfreemove(this)">Freemove</label></div></div>
             <div class="col-xs-12 col-md-4 col-lg-2"><div class="checkbox"><label><input type="checkbox" value="" '.(strpos($motd, $fleetlink) ? 'checked ':'').'onchange="setmotd(this)">Fleet-Yo Link in motd</label></div></div>
             <div class="col-xs-12 col-md-6 col-lg-3 tt-pilot form">
               <link href="css/typeaheadjs.css" rel="stylesheet">
               <input type="text" class="typeahead form-control" placeholder="invite to fleet">
               <input id="inv-id" type="hidden" values="">
               <button type="button" id="inv-button" class="tt-btn btn btn-primary disabled" onclick="fleetinvite()"><span class="glyphicon glyphicon-envelope"></span></button>
             </div>
           </div>';
    $fh .= '<script>
        function setpublic(cb) {
            var state = cb.checked;
            $.ajax({
                type: "POST",
                url: "'.URL::url_path().'ajax/aj_public.php",
                data: {"fid" : '.$fleet->getFleetID().', "ajtok" : "'.$_SESSION['ajtoken'].'", "state" : state},
                success:function(data)
                {
                  if (data !== "true") {
                      BootstrapDialog.show({message: "something went wrong", type: BootstrapDialog.TYPE_WARNING});
                  }
                }
                });
        }
        function setfreemove(cb) {
            var state = cb.checked;
            $.ajax({
                type: "POST",
                url: "'.URL::url_path().'ajax/aj_freemove.php",
                data: {"fid" : '.$fleet->getFleetID().', "ajtok" : "'.$_SESSION['ajtoken'].'", "state" : state},
                success:function(data)
                {
                  if (data !== "true") {
                      BootstrapDialog.show({message: "something went wrong", type: BootstrapDialog.TYPE_WARNING});
                  }
                }
                });
        }
        function setmotd(cb) {
            var state = cb.checked;
            $.ajax({
                type: "POST",
                url: "'.URL::url_path().'ajax/aj_motd.php",
                data: {"fid" : '.$fleet->getFleetID().', "ajtok" : "'.$_SESSION['ajtoken'].'", "state" : state, "motd" : "blabala"},
                success:function(data)
                {
                  if (data == "false") {
                      BootstrapDialog.show({message: "something went wrong", type: BootstrapDialog.TYPE_WARNING});
                  } else {
                      $( "#motd" ).html("MOTD:<br/>"+data);
                  }
                }
                });
        }
        function fleetinvite() {
             var char_id = $("#inv-id").val();
             $.ajax({
                type: "POST",
                url: "'.URL::url_path().'ajax/aj_fleetinvite.php",
                data: {"fid" : '.$fleet->getFleetID().', "ajtok" : "'.$_SESSION['ajtoken'].'", "cid" : char_id },
                success:function(data)
                {
                  if (data !== "true") {
                      BootstrapDialog.show({message: data, type: BootstrapDialog.TYPE_WARNING});
                  } else {
                      BootstrapDialog.show({message: "Fleet invite sent."});
                  }
                }
             });
        }
    </script>';
    return $fh;
}

function getFleetTable($fleet, $hasRights=false) {
    if (!isset($_SESSION['ajtoken'])) {
        $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
    }
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
          if ($hasRights) {
              $table .='<th style="text-align: center" class="no-sort">backupfc</th>';
          }
        $table .='</tr>
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
          if ($hasRights) {
              $table .='<td align="center"><input type="checkbox" value="" '.(($m['backupfc']) ? 'checked ':'').'onchange="backupfc(this)"></td>';
          }
          $table .='</tr>';
      }
      $table .='</tbody></table>';
    if ($hasRights) {
        $table .='<script>
        function backupfc(cb) {
            var id = $(cb).closest("tr").attr("id");
            var state = cb.checked;
            $.ajax({
                type: "POST",
                url: "'.URL::url_path().'ajax/aj_backupfc.php",
                data: {"fid" : '.$fleet->getFleetID().', "cid" : id, "ajtok" : "'.$_SESSION['ajtoken'].'", "state" : state},
                success:function(data)
                {
                  if (data !== "true") {
                      BootstrapDialog.show({message: "something went wrong", type: BootstrapDialog.TYPE_WARNING});
                  }
                }
                }); 
        }
        </script>';
    }
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/dataTables.bootstrap.min.js"></script>
    <script src="js/typeahead.bundle.min.js"></script>
    <script src="js/esi_autocomplete.js"></script>
    <script src="js/bootstrap-dialog.min.js"></script>
    <link href="css/bootstrap-dialog.min.css" rel="stylesheet">';
    return $html;
}

?>
