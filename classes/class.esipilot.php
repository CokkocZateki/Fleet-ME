<?php
require_once('config.php');
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;
use Swagger\Client\Api\LocationApi;
use Swagger\Client\Api\UniverseApi;

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
    protected $stationName = 'in space';
    protected $structureID = null;
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
        $refresh = false;
        if($result->num_rows) {
            $row = $result->fetch_assoc();
            $this->locationID = $row['locationID'];
            $this->shipTypeID = $row['shipTypeID'];
            $this->stationID = $row['stationID'];
            $this->structureID = $row['structureID'];
            $this->backupfc = $row['backupfc'];
            $this->fitting = $row['fitting'];
            $this->lastfetch = strtotime($row['lastFetch']);
            if (strtotime("now")-$this->lastfetch > 60 ) {
                $refresh = true;
            } else {
                if (isset($this->stationID) && $this->stationID != 0) {
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

                    if (isset($this->structureID) && $this->structureID != 0) {
                        $qry = DB::getConnection();
                        $sql="SELECT structureName FROM structures WHERE structureID = ".$this->structureID;
                        $result = $qry->query($sql);
                        if($result->num_rows) {
                            $row=$result->fetch_assoc();
                            $this->stationName = $row['structureName'];
                        }
                    }
                }
                $qry = DB::getConnection();
                $sql="SELECT typeName FROM invTypes WHERE typeID = ".$this->shipTypeID;
                $result = $qry->query($sql);
                if($result->num_rows) {
                    $row=$result->fetch_assoc();
                    $this->shipTypeName = $row['typeName'];
                }
            }
        } else {
            $refresh = true;
        }
        if ($refresh) {
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
                    $qry = DB::getConnection();
                    $sql="SELECT structureName FROM structures WHERE structureID = ".$this->structureID;
                    $result = $qry->query($sql);
                    if($result->num_rows) {
                        $row=$result->fetch_assoc();
                        $this->stationName = $row['structureName'];
                    } else {
                        if (!isset($esiapi)) {
                            $esiapi = new ESIAPI();
                        }
                        $esiapi->setAccessToken($this->accessToken);
                        $universeapi = new UniverseApi();
                        $structureinfo = json_decode($universeapi->getUniverseStructuresStructureId($this->structureID, 'tranquility'));
                        $this->stationName = $structureinfo->name;
                        $sql="INSERT INTO structures (solarSystemID,structureID,structureName,lastUpdate) VALUES ({$structureinfo->solar_system_id},{$this->structureID},'{$this->stationName}',NOW())";
                        $result = $qry->query($sql);
                    }
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
            $qry = DB::getConnection();
            $sql="SELECT typeName FROM invTypes WHERE typeID = ".$this->shipTypeID;
            $result = $qry->query($sql);
            if($result->num_rows) {
                $row=$result->fetch_assoc();
                $this->shipTypeName = $row['typeName'];
            }
            $qry = DB::getConnection();
            $sql="REPLACE INTO pilots (characterID,locationID,shipTypeID,stationID,structureID,lastFetch)
                  VALUES ({$this->characterID},'{$this->locationID}',{$this->shipTypeID},'{$this->stationID}','{$this->structureID}',NOW())";
            $result = $qry->query($sql);
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

        public function getShipTypeID() {
                return $this->shipTypeID;
        }

        public function getShipTypeName() {
                return $this->shipTypeName;
        }

        public function getShipName() {
                return $this->shipName;
        }

}
?>
