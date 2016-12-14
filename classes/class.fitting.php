<?php
include_once('config.php');

class FITTING
{
    protected $fitting = array();
    protected $shipID = null;
    protected $highs = array();
    protected $meds = array();
    protected $lows = array();
    protected $rigs = array();
    protected $subsys = array();
    protected $drones = array();
    protected $error = false;
    protected $message = '';

    public function __construct($fit) {
        $temp = array();
        $tempmods = array();
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $fit) as $line){
            if (0 === strpos($line, '[')) {
                $temp[] = array(0 => preg_split('/[\[,]/', $line)[1]);
            } else {
                if (trim($line) == '') {
                    $temp[] = $tempmods;
                    $tempmods = array();
                } else {
                    $tempmods[] = (preg_split('/[,]/', $line)[0]);
                }
            }
        }
        foreach ($temp as $i => $values) {
            if (count($values)) {
                $qry = DB::getConnection();
                $escapednames = array();
                foreach ($values as $value) {
                    $escapednames[] = $qry->real_escape_string($value);
                }
                $typenames = implode("' OR typeName='",$escapednames);
                $sql="SELECT typeID, typeName FROM invTypes WHERE typeName='".$typenames."'";
                $result = $qry->query($sql);
                while ($row = $result->fetch_row()) {
                    foreach ($values as $name) {
                        if ($row[1] == $name) {
                            switch ($i) {
                               case 0:
                                   $this->shipID = $row[0];
                                   break;
                               case 1:
                                   $this->lows[] = $row[0];
                                   break;
                               case 2:
                                   $this->meds[] = $row[0];
                                   break;
                               case 3:
                                   $this->highs[] = $row[0];
                                   break;
                               case 4:
                                   $this->rigs[] = $row[0];
                                   break;
                               case 5:
                                   $this->subsys[] = $row[0];
                                   break;
                               case 6:
                                   $this->drones[] = $row[0];
                                   break;
        
                            }
                        }
                    }
                }
            }
        }
        if ($this->shipID == null) {
            $this->error = true;
            $this->message = "Fitting could not be parsed";
        }
        $this->fitting['ship'] = $this->shipID;
        $this->fitting['lows'] = $this->lows;
        $this->fitting['meds'] = $this->meds;
        $this->fitting['highs'] = $this->highs;
        $this->fitting['rigs'] = $this->rigs;
        $this->fitting['subsys'] = $this->subsys;
        $this->fitting['drones'] = $this->drones;
    }

    public function addToChar($characterID) {
        if ($this->error) {
            return false;
        }
        $fit = json_encode($this->fitting, JSON_NUMERIC_CHECK);
        $qry = DB::getConnection();
        $sql="SELECT shipTypeID FROM pilots WHERE characterID = ".$characterID;
        $result = $qry->query($sql);
        if($result->num_rows) {
            $row=$result->fetch_assoc();
            $shiptypeid = $row['shipTypeID'];
            if ($shiptypeid == $this->shipID) {
                $sql="UPDATE pilots SET fitting='{$fit}' WHERE characterID = ".$characterID;
                $qry->query($sql);
                $this->message = "Fitting updated.";
                return true;
            } else {
                $this->error = true;
                $this->message = "Fitting does not match your current ship.";
                return false;
            }
        } else {
            $this->error = true;
            $this->message = "Pilot not found in the database";
            return false;
        }
    }

    public function getShipTypeID() {
        return $this->shipID;
    }

    public function getError() {
        return $this->error;
    }

    public function getMessage() {
        return $this->message;
    }

}
?>
