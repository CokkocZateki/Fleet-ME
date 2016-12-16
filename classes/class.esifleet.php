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
    

    public function __construct($fleetID, $characterID, $dbonly=false) {
        $this->fleetID = $fleetID;        
        parent::__construct(null, $characterID);
        $qry = DB::getConnection();
        $sql = "SELECT * FROM fleets WHERE fleetID=".$fleetID;
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row=$result->fetch_assoc();
            $this->fc = $row['fc'];
            $this->boss = $row['boss'];
            $this->public = $row['public'];
            $this->created = strtotime($row['created']);
            $this->lastFetch = strtotime($row['lastFetch']);
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
                $this->fc = $characterID;
                $sql = "INSERT INTO fleets (fleetID,boss,fc,created,lastFetch) VALUES ({$this->fleetID},{$this->boss},{$this->fc},NOW(),NOW())";
                $qry->query($sql);
                $sql = "DELETE FROM fleetmembers WHERE fleetID=".$this->fleetID;
                $qry->query($sql);
                foreach ($fleetmembers as $member) {
                    $this->members[] = $member->getCharacterId();
                }
            }
        } else {
            $this->fleetID = null;
        }
    }
    public static function getFleetForChar($characterID) {
        $qry = DB::getConnection();
        $sql = "SELECT fleets.fleetID, fleets.boss FROM fleetmembers LEFT JOIN fleets ON fleets.fleetID=fleetmembers.fleetID WHERE characterID=".$characterID;
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

}
?>
