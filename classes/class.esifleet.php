<?php
require_once('config.php');
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;
use Swagger\Client\Api\UniverseApi;
use Swagger\Client\Api\FleetsApi;

require_once('classes/esi/autoload.php');
require_once('classes/class.esisso.php');
require_once('classes/class.url.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

// Credit to FuzzySteve https://github.com/fuzzysteve/eve-sso-auth/
class ESIFLEET extends ESISSO
{
    protected $fleetID = null;
    protected $fc = null;
    protected $boss = null;
    protected $members = array();
    protected $backupfcs = array();
    protected $public = false;
    protected $created = null;
    protected $lastFetch = null;
    protected $freemove = false;
    protected $motd = null;
    

    public function __construct($fleetID, $characterID, $dbonly=false) {
        $this->fleetID = $fleetID;        
        parent::__construct(null, $characterID);
        $qry = DB::getConnection();
        $sql = "SELECT * FROM fleets WHERE fleetID=".$fleetID." AND fleets.created >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row=$result->fetch_assoc();
            $this->fc = $row['fc'];
            $this->boss = $row['boss'];
            $this->public = $row['public'];
            $this->created = strtotime($row['created']);
            $this->lastFetch = strtotime($row['lastFetch']);
            $sql = "SELECT fm.characterID, p.characterName, fm.backupfc, p.shipTypeID, p.fitting, p.locationID, p.stationID, p.structureID, fm.wingID, fm.squadID, fm.role, fm.fleetWarp, fm.joined FROM fleetmembers as fm 
                LEFT JOIN pilots as p ON p.characterID=fm.characterID WHERE fleetID=".$fleetID;
            if ($stmt = $qry->prepare($sql)) {
                $stmt->execute();
                $stmt->bind_result($id, $name, $backup, $ship, $fit, $system, $station, $structure, $wing, $squad, $role, $fleetwarp, $joined);
                while ($stmt->fetch()) {
                    $this->members[] = array('id' => $id,
                                         'name' => $name,
                                         'backupfc' => $backup,
                                         'joined' => strtotime($joined),
                                         'role' => $role,
                                         'ship' => $ship,
                                         'fit' => $fit,
                                         'system' => $system,
                                         'station' => $station,
                                         'station' => $structure,
                                         'wing' => $wing,
                                         'squad' => $squad,
                                         'fleetwarp' => $fleetwarp );
                }
                $stmt->close();
            }        


        } elseif (!$dbonly) {
            $esiapi = new ESIAPI();
            $esiapi->setAccessToken($this->accessToken);
            $fleetapi = new FleetsApi($esiapi);
            try {
                $fleetinfo = $fleetapi->getFleetsFleetId($fleetID, 'tranquility');
                $fleetmembers = $fleetapi->getFleetsFleetIdMembers($fleetID, 'en', 'tranquility');
            } catch (ApiException $e) {
                $this->error = true;
                $this->message = 'Could not find Fleet: '.$e->getMessage().PHP_EOL;
            }
            if (!$this->error) {
                $this->boss = $characterID;
                $this->fc = null;
                $this->freemove = $fleetinfo->getIsFreeMove();
                $this->motd = $fleetinfo->getMotd();
                $sql = "DELETE FROM fleetmembers WHERE fleetID=".$this->fleetID;
                $qry->query($sql);
                foreach ($fleetmembers as $member) {
                    $this->members[] = array('id' => $member->getCharacterId(),
                                             'backupfc' => false, 
                                             'joined' => $member->getJoinTime(),
                                             'role' => $member->getRole(),
                                             'ship' => $member->getShipTypeId(),
                                             'fit' => null,
                                             'system' => $member->getSolarSystemId(),
                                             'station' => $member->getStationId(),
                                             'structure' => null,
                                             'wing' => $member->getWingId(),
                                             'squad' => $member->getSquadId(),
                                             'fleetwarp' => $member->getTakesFleetWarp() );
                    if ($member->getRole() == 'fleet_commander') {
                        $this->fc = $member->getCharacterId();
                    }
                }
                $sql = "REPLACE INTO fleets (fleetID,boss,fc,created,lastFetch) VALUES ({$this->fleetID},{$this->boss},'{$this->fc}',NOW(),NOW())";
                $qry->query($sql);
                foreach($this->members as $m) {
                    $sql = "REPLACE INTO fleetmembers (characterID, fleetID, backupfc, wingID, squadID, role, fleetWarp, joined)
                           VALUES ({$m['id']},{$this->fleetID},FALSE,{$m['wing']},{$m['squad']},'{$m['role']}', {$m['fleetwarp']},'".$m['joined']->format('Y-m-d H:i:s')."')";
                    $qry->query($sql);
                }
                $this->update();
            }
        } else {
            $this->fleetID = null;
        }
    }

    public static function getFleetForChar($characterID) {
        $qry = DB::getConnection();
        $sql = "SELECT fleets.fleetID, fleets.boss FROM fleetmembers LEFT JOIN fleets ON fleets.fleetID=fleetmembers.fleetID WHERE characterID=".$characterID." AND fleets.created >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row=$result->fetch_assoc();
            $fleet = new ESIFLEET($row['fleetID'], $row['boss'], true);
            if ($fleet->getFleetID() == null) {
                return false;
            } else {
                return $fleet;
            }
        } else {
            return false;
        }
    }

    public function update() {
        $esiapi = new ESIAPI();
        $esiapi->setAccessToken($this->accessToken);
        $fleetapi = new FleetsApi($esiapi);
        try {
            $fleetinfo = $fleetapi->getFleetsFleetId($this->fleetID, 'tranquility');
            $fleetmembers = $fleetapi->getFleetsFleetIdMembers($this->fleetID, 'en', 'tranquility');
        } catch (ApiException $e) {
            $this->error = true;
            if ($e->getCode() == 403) {
                $this->message = 'Looks like the fleet Boss dropped the fleet or has handed over fleet boss. If you\'re fleet boss register the fleet <a href="'.URL::url_path().'registerfleet.php">here</a>.';
            } else {
                $this->message = 'Could not refresh your last Fleet: '.$e->getMessage().PHP_EOL;
            }
            return false;
        }
        $this->freemove = $fleetinfo->getIsFreeMove();
        $this->motd = $fleetinfo->getMotd();
        $this->members = array();
        $dbmembers = array();
        $qry = DB::getConnection();
        $sql = "SELECT fm.fleetID as fleet, fm.characterID as id, p.shipTypeID as ship, p.fitting as fit, p.characterName as name, p.locationID as location, p.stationID as station, fm.backupfc as bfc, p.lastFetch as lastFetch
                FROM fleetmembers as fm LEFT JOIN pilots as p ON p.characterID=fm.characterID";
        $result = $qry->query($sql);
        while ($row = $result->fetch_assoc()) {
            $dbmembers[$row['id']] = array('fleet' => $row['fleet'], 'ship' => $row['ship'], 'fit'=> $row['fit'], 'name' => $row['name'], 'system' => $row['location'], 'station' => $row['station'], 'bfc' => $row['bfc'], 'lastFetch' => strtotime($row['lastFetch']));
        }
        foreach ($fleetmembers as $member) {
            $this->members[] = array('id' => $member->getCharacterId(),
                                     'backupfc' => false,
                                     'joined' => $member->getJoinTime(),
                                     'role' => $member->getRole(),
                                     'ship' => $member->getShipTypeId(),
                                     'fit' => null,
                                     'system' => $member->getSolarSystemId(),
                                     'station' => $member->getStationId(),
                                     'structure' => null,
                                     'wing' => $member->getWingId(),
                                     'squad' => $member->getSquadId(),
                                     'fleetwarp' => $member->getTakesFleetWarp() );
        }
        foreach($this->members as $i => $m) {
            if (isset($dbmembers[$m['id']])) {
                if ($dbmembers[$m['id']]['name'] != null && $dbmembers[$m['id']]['name'] != '') {
                    $this->members[$i]{'name'} = $dbmembers[$m['id']]['name'];
                }
                $m['backupfc'] = $this->members[$i]['backupfc'] = $dbmembers[$m['id']]['bfc'];
                $sql = "UPDATE fleetmembers SET fleetID={$this->fleetID}, wingID={$m['wing']}, squadID={$m['squad']},role='{$m['role']}',fleetWarp={$m['fleetwarp']} WHERE characterID={$m['id']}";
                $qry->query($sql);
                if ($m['system'] != $dbmembers[$m['id']]['system'] || ((int)$m['station'] != (int)$dbmembers[$m['id']]['station'])) {
                    if ($m['ship'] != $dbmembers[$m['id']]['ship']) {
                        if (strtotime("now") - $dbmembers[$m['id']]['lastFetch'] < 30) {
                            //$sql="UPDATE pilots SET locationID='{$m['system']}',stationID='{$m['station']}',
                            //    structureID='',lastFetch=NOW() WHERE characterID={$m['id']}";
                            $m['fit'] = $this->members[$i]['fit'] = $dbmembers[$m['id']]['fit'];
                            $m['ship'] = $this->members[$i]['ship'] = $dbmembers[$m['id']]['ship'];
                        } else {
                            $sql="UPDATE pilots SET locationID='{$m['system']}',shipTypeID={$m['ship']},stationID='{$m['station']}',
                                  structureID='',fitting=NULL,lastFetch=NOW() WHERE characterID={$m['id']}"; 
                        }
                    } else {
                        $sql="UPDATE pilots SET locationID='{$m['system']}',stationID='{$m['station']}',
                              structureID='',lastFetch=NOW() WHERE characterID={$m['id']}";
                        $m['fit'] = $this->members[$i]['fit'] = $dbmembers[$m['id']]['fit'];
                    }
                } elseif ($m['ship'] != $dbmembers[$m['id']]['ship']) {
                    if (strtotime("now") - $dbmembers[$m['id']]['lastFetch'] < 30) {
                        $m['fit'] = $this->members[$i]['fit'] = $dbmembers[$m['id']]['fit'];
                        $m['ship'] = $this->members[$i]['ship'] = $dbmembers[$m['id']]['ship'];
                    } else {
                        $sql="UPDATE pilots SET shipTypeID={$m['ship']},fitting=NULL,lastFetch=NOW() WHERE characterID={$m['id']}";
                    }
                }
                $qry->query($sql);
                unset($dbmembers[$m['id']]);
                if ($m['role'] == 'fleet_commander') {
                    $this->fc = $m['id'];
                }
            } else {
                    $esiapi = new ESIAPI();
                    $charapi = new CharacterApi($esiapi);
                    try {
                        $charinfo = json_decode($charapi->getCharactersCharacterId($m['id'], 'tranquility'));
                        $characterName = $charinfo->name;
                        $m['name'] = $this->members[$i]['name'] = $characterName;
                    } catch (Exception $e) {
                        $m['name'] = $this->members[$i]['name'] = null;
                    }
                    $sql = "REPLACE INTO fleetmembers (characterID, fleetID, backupfc, wingID, squadID, role, fleetWarp, joined)
                            VALUES ({$m['id']},{$this->fleetID},FALSE,{$m['wing']},{$m['squad']},'{$m['role']}', {$m['fleetwarp']},'".$m['joined']->format('Y-m-d H:i:s')."')";
                    $qry->query($sql);
                    $sql = "REPLACE INTO pilots (characterID,characterName,locationID,shipTypeID,stationID,structureID,fitting,lastFetch) VALUES ({$m['id']},'{$m['name']}',{$m['system']},{$m['ship']},'{$m['station']}',NULL,NULL,NOW())";
                    $qry->query($sql);
            }
        }
        if (count($dbmembers)) {
            $dbleft = array_keys($dbmembers);
            $sql="DELETE FROM fleetmembers WHERE (characterID=".implode(" OR characterID=", $dbleft).") AND fleetID =".$this->fleetID;
            $qry->query($sql);
        }
        $sql = "UPDATE fleets SET fc='{$this->fc}', lastFetch=NOW() WHERE fleetID =".$this->fleetID;
        $qry->query($sql);
        return true;
    }

    public function getFleetID() {
        return $this->fleetID;
    }

    public function getBoss() {
        return $this->boss;
    }

    public function getFC() {
        return $this->fc;
    }

    public function isPublic() {
        return $this->public;
    }

    public function getBackupFCs() {
        return $this->backupfcs;
    }

    public function getMembers() {
        return $this->members;
    }

    public function getMotd() {
        return $this->motd;
    }

    public function getFreemove() {
        return $this->freemove;
    }
}
?>
