<?php
require_once('config.php');
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;
use Swagger\Client\Api\LocationApi;

require_once('classes/esi/autoload.php');
require_once('classes/class.esisso.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

// Credit to FuzzySteve https://github.com/fuzzysteve/eve-sso-auth/
class ESIPILOT extends ESISSO
{
    protected $locationID = null;
    protected $locationName = null;
    protected $fleetID = null;
    protected $shipTypeName = null;
    protected $shipTypeID = null;
    protected $shipName = null;
    protected $stationID = null;
    protected $stationName = null;
    protected $structureID = null;
    protected $structureName = null;
    protected $backupfc = false;
    protected $fitting = null;
    protected $lastFetch = null;

    public function __construct($characterID) {
        parent::__construct(null, $characterID);
        if ($this->characterName == null) {
            $esiapi = new ESIAPI();
            $charapi = new CharacterApi($esiapi);
            try {
                $charinfo = json_decode($charapi->getCharactersCharacterId($this->characterID, 'tranquility'));
                $this->characterName = $charinfo->name;
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not relove character name: '.$e->getMessage().PHP_EOL;
                return false;
            }
        }
        $sql="SELECT * FROM pilots WHERE characterID=".$this->characterID;
        $qry = DB::getConnection();
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            $this->locationID = $row['locationID'];
            $this->shipTypeID = $row['shipTypeID'];
            $this->stationID = $row['stationID'];
            $this->structureID = $row['structureID'];
            $this->backupfc = $row['backupfc'];
            $this->fitting = $row['fitting'];
            $this->lastfetch = strtotime($row['backupfc']);
        } else {
            if (!isset($esiapi)) {
                $esiapi = new ESIAPI();
            }
            $esiapi->setAccessToken($this->accessToken);
            $locationapi = new LocationApi();
            try {
                $locationinfo = json_decode($locationapi->getCharactersCharacterIdLocation($this->characterID, 'tranquility'));
                $this->locationID = $locationinfo->solar_system_id;
                if (isset($locationinfo->station_id)) {
                    $this->stationID = $locationinfo->station_id;
                    $sql="SELECT mapSolarSystems.solarSystemName as systemName, mapDenormalize.itemName as stationName FROM `mapSolarSystems` INNER JOIN mapDenormalize on mapSolarSystems.solarSystemID = mapDenormalize.solarSystemID WHERE mapSolarSystems.solarSystemID = ".$this->locationID." AND mapDenormalize.itemID =".$this->stationID;
                    $qry = DB::getConnection();
                    $result = $qry->query($sql);
                    if($result->num_rows) {
                        $row=$result->fetch_assoc();
                        $this->locationName = $row['systemName'];
                        $this->stationName = $row['stationName'];
                    } 
                } else {
                    $this->stationID = null;
                    $qry = DB::getConnection();
                    $sql="SELECT solarSystemName as systemName FROM mapSolarSystems WHERE solarSystemID = ".$this->locationID;
                    $result = $qry->query($sql);
                    if($result->num_rows) {
                        $row=$result->fetch_assoc();
                        $this->locationName = $row['systemName'];
                    }

                }
                if (isset($locationinfo->structure_id)) {
                    $this->structureID = $locationinfo->structure_id;
                } else {
                    $this->structureID = null;
                }
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not get location Info: '.$e->getMessage().PHP_EOL;
                return false;
            }

            try {
                $shipinfo = json_decode($locationapi->getCharactersCharacterIdShip($this->characterID, 'tranquility'));
                $this->shipTypeID = $shipinfo->ship_type_id;
                $shipUniqueID = $shipinfo->ship_item_id;
                $this->shipName = $shipinfo->ship_name;
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not get location Info: '.$e->getMessage().PHP_EOL;
                return false;
            }

        }
    }

        public function getLocationID() {
                return $this->locationID;
        }

        public function getLocationName() {
                return $this->locationName;
        }

        public function getStationID() {
                return $this->stationID;
        }

        public function getStationName() {
                return $this->stationName;
        }

}
?>
