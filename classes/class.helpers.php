<?php
include_once('config.php');
class EVEHELPERS {

    public static function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }


    private static function flatten(array $array) {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    public static function getInvNames($items) {
        $qry = DB::getConnection();
        $sql="SELECT typeID, typeName FROM invTypes WHERE typeID=".implode(" OR typeID=", self::flatten($items));
        $result = $qry->query($sql);
        $return = array();
        if($result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                $return[$row['typeID']] = $row['typeName'];
            }
            return $return;
        } else {
            return null;
        }
    }

    public static function getInvGroupNames($items) {
        $qry = DB::getConnection();
        $sql="SELECT invTypes.typeID, invGroups.groupName FROM invTypes LEFT JOIN invGroups ON invTypes.groupID = invGroups.groupID
              WHERE typeID=".implode(" OR typeID=", self::flatten($items));
        $result = $qry->query($sql);
        $return = array();
        if($result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                $return[$row['typeID']] = $row['groupName'];
            }
            return $return;
        } else {
            return null;
        }
    }

    public static function getSystemNames($items) {
        $qry = DB::getConnection();
        $sql="SELECT solarSystemID, solarSystemName FROM mapSolarSystems WHERE solarSystemID=".implode(" OR solarSystemID=", self::flatten($items));
        $result = $qry->query($sql);
        $return = array();
        if($result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                $return[$row['solarSystemID']] = $row['solarSystemName'];
            }
            return $return;
        } else {
            return null;
        }
    }

}
?>
