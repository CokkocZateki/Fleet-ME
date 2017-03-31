<?php
require_once('config.php');
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

use Swagger\Client\ApiClient;
use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;

require_once('classes/esi/autoload.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

// Credit to FuzzySteve https://github.com/fuzzysteve/eve-sso-auth/
class ESISSO
{
  private $code = null;
  protected $accessToken = null;
  private $refreshToken = null;
  private $scopes = array();
  private $ownerHash = null;
  protected $characterID = 0;
  protected $characterName = null;
  protected $error = false;
  protected $message = null;
  protected $failcount = 0;
  protected $enabled = true;
  protected $id = null;
  protected $expires = null;
  protected $log;

	function __construct($id = null, $characterID = 0, $refreshToken = null, $failcount = 0)
	{
                $this->log = new LOG('log/esi.log');
                if($id != null) {
                        $this->id = $id;
                        $sql="SELECT * FROM esisso WHERE id=".$id;
                        $qry = DB::getConnection();
                        $result = $qry->query($sql);
                        if($result->num_rows) {
                                $row = $result->fetch_assoc();
                        	$this->characterID = $row['characterID'];
                                $this->characterName = $row['characterName'];
                                $this->refreshToken = $row['refreshToken'];
                                $this->accessToken = $row['accessToken'];
                                $this->ownerHash = $row['ownerHash'];
                                $this->failcount = $row['failcount'];
                                $this->enabled = $row['enabled'];
                                $this->expires = strtotime($row['expires']);
                                if ($this->hasExpired()) {
                                    $this->refresh(false);
                                }
                        }		
		} elseif ($characterID != 0) {
			$this->characterID = $characterID;
			$qry = DB::getConnection();
			$sql="SELECT * FROM esisso WHERE (characterID='".$characterID."')";
			$result = $qry->query($sql);
			if($result->num_rows) {
                                $row = $result->fetch_assoc();
				$this->id = $row['id'];
                                $this->characterName = $row['characterName'];
				$this->refreshToken = $row['refreshToken'];
                                $this->accessToken = $row['accessToken'];
                                $this->ownerHash = $row['ownerHash'];
                                $this->failcount = $row['failcount'];
                                $this->enabled = $row['enabled'];
                                $this->expires = strtotime($row['expires']);
                                if ($this->hasExpired()) {
				    $this->refresh(false);
                                }
			}
		} elseif (isset($this->refreshToken)) {
			$this->refreshToken = $refreshToken;
			$this->refresh();
		}
	}

	public function setCode($code) {
		$this->code = $code;

                $url = 'https://login.eveonline.com/oauth/token';
                $header = 'Authorization: Basic '.base64_encode(ESI_ID.':'.ESI_SECRET);
                $fields_string = '';
                $fields = array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    );
                foreach ($fields as $key => $value) {
                    $fields_string .= $key.'='.$value.'&';
                }
                rtrim($fields_string, '&');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                $result = curl_exec($ch);
                if ($result === false) {
                    $this->error = true;
                    $this->message = (curl_error($ch));
                    $this->log->error($this->message);
                }
                curl_close($ch);
                if (!$this->error){
                    $response = json_decode($result);
                    $this->accessToken = $response->access_token;
                    $this->expires = (strtotime("now")+1000);
                    $this->refreshToken = $response->refresh_token;
                    $result = $this->verify();
                    return $result;
                } else {
                    return false;
                }
	}

        public function verify() {
		if (!isset($this->accessToken)) {
                    $this->error = true;
                    $this->message = "No Acess Token to verify.";
                    return false;
		} else {
                    $verify_url = 'https://login.eveonline.com/oauth/verify';
                    $ch = curl_init();
                    $header = 'Authorization: Bearer '.$this->accessToken;
                    curl_setopt($ch, CURLOPT_URL, $verify_url);
                    curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    $result = curl_exec($ch);
                    if ($result === false) {
                        $this->error = true;
                        $this->message = (curl_error($ch));
                        $this->log->error($this->message);
                    }
                    curl_close($ch);
                    if ($this->error) {
			return false;
		    }
                        $response = json_decode($result);
                        if (isset($response->error)) {
                            $this->error = true;
                            $this->message = $response->error;
                            $this->log->error($this->message);
                            return false;
                        }
                        if (!isset($response->CharacterID)) {
                            $this->error = true;
                            $this->message = "Failed to get character ID.";
                            $this->log->error($this->message);
                            return false;
                        }
                        $this->characterID = $response->CharacterID;
                        $this->characterName = $response->CharacterName;
                        $this->scopes = explode(' ', $response->Scopes);
                        if ($this->scopes == null || $this->scopes == '') {
                            $this->error = true;
                            $this->message = 'Scopes missing.';
                            $this->log->error($this->message);
                            return false;
                        }
                        $this->ownerHash = $response->CharacterOwnerHash;
                }
		return true;
	}

	public function addToDb() {
		$refreshToken = $this->refreshToken;
		$ownerHash = $this->ownerHash;
		$characterID = $this->characterID;
                $characterName = $this->characterName;
                $accessToken = $this->accessToken;
                $expires = date('Y-m-d H:i:s', $this->expires);
		$failcount = 0;
		$enabled = true;
		$qry = DB::getConnection();
		$result = $qry->query("SELECT * FROM esisso WHERE (characterID='".$characterID."')");
                if ($result->num_rows == 0) {
                        $esiapi = new ESIAPI();
                        $charapi = new CharacterApi($esiapi);
                        try {
                            $charinfo = json_decode($charapi->getCharactersCharacterId($characterID, 'tranquility'));
                            $characterName = $charinfo->name;
                            $this->characterName = $characterName;
                        } catch (Exception $e) {
                            $this->error = true;
                            $this->message = 'Could not relove character name: '.$e->getMessage();
                            $this->log->error($this->message);
                            return false;
                        }
                	$stmt = $qry->prepare("INSERT into esisso (characterID,characterName,refreshToken,accessToken,expires,ownerHash,failcount,enabled) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param('isssssii', $cid, $cn, $rt, $at, $exp, $oh, $fc, $en);
                            $cid = $characterID;
                            $cn = $characterName;
                            $rt = $refreshToken;
                            $at = $accessToken;
                            $exp = $expires;
                            $oh = $ownerHash;
                            $ft = 0;
                            $en = 1;
			    $stmt->execute();
                            if ($stmt->errno) {
				$this->error = true;
				$this->message = $stmt->error;
                                $this->log->error($this->message);
				return false;
                            }
                        }
                        $this->message = 'SSO credentials succesfully added.';
		} else {
			$row = $result->fetch_assoc();
			$id = $row['id'];
                        $this->characterName = $row['characterName'];
			$sql="UPDATE esisso SET characterID={$characterID},refreshToken='{$refreshToken}',
                              accessToken='{$accessToken}',expires='{$expires}',ownerHash='{$ownerHash}',failcount=0,enabled=TRUE WHERE id={$id};";
                        $result = $qry->query($sql);
                        if (!$result) {
                                $this->error = true;
                                $this->message = $qry->getErrorMsg();
                                $this->log->error($this->message);
                                return false;
                        }
                        $this->message = 'SSO credentials updated.';
		}
                return true;
	}
	public function refresh( $verify = true ) {
                if (!isset($this->refreshToken)) {
		    $this->error = true;
                    $this->message = "No refresh token set.";
                    $this->log->error($this->message);
                    return false;
		}
	        $fields = array('grant_type' => 'refresh_token', 'refresh_token' => $this->refreshToken);
       		$url = 'https://login.eveonline.com/oauth/token';
	        $header = 'Authorization: Basic '.base64_encode(ESI_ID.':'.ESI_SECRET);
	        $fields_string = '';
	        foreach ($fields as $arrKey => $value) {
	            $fields_string .= $arrKey.'='.$value.'&';
	        }
	        $fields_string = rtrim($fields_string, '&');
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_USERAGENT, ESI_USER_AGENT);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
	        curl_setopt($ch, CURLOPT_POST, count($fields));
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
 	        $result = curl_exec($ch);
                if ($result === false) {
                    $this->error = true;
                    $this->message = (curl_error($ch));
                    $this->log->error($this->message);
                }
 	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
                if ($httpCode < 199 || $httpCode > 299) {
                    $this->error = true;
                    $this->message = ("Error: Response ".$httpCode." when refreshing the Access Token.");
                    $this->log->error($this->message);
                }
                if ($this->error) {
                    $this->increaseFailCount();
                    return false;
                }
		$response = json_decode($result, true);
      		$this->accessToken = $response['access_token'];
                $this->expires = (strtotime("now")+1000);
                $qry = DB::getConnection();
                $expires = date('Y-m-d H:i:s', $this->expires);
                $sql="UPDATE esisso SET accessToken='{$this->accessToken}',expires='{$expires}' WHERE characterID={$this->characterID};";
                $result = $qry->query($sql);
                if (!$result) {
                        $this->error = true;
                        $this->message = $qry->getErrorMsg();
                        $this->log->error($this->message);
                        return false;
                }

                if ($verify) {
		    $this->verify();
                }
                $this->resetFailCount();
		return true;
	}

	public function increaseFailCount() {
                $this->failcount+=1;
                $qry = DB::getConnection();
                if ($this->failcount >= 10) { 
			$sql="UPDATE esisso SET failcount={$this->failcount},enabled=FALSE WHERE id={$this->id};";
                } else {
                        $sql="UPDATE esisso SET failcount={$this->failcount} WHERE id={$this->id};";
                }
                $result = $qry->query($sql);
	}

        public function resetFailCount() {
                if ($this->failcount != 0) {
                	$this->failcount = 0;
                	$qry = DB::getConnection();
                        $sql="UPDATE esisso SET failcount=0 WHERE id={$this->id};";
	                $result = $qry->query($sql);
                }
        }


        public function getError() {
		return $this->error;
	}

        public function setMessage($message) {
                $this->message = $message;
        }

        public function getMessage() {
                return $this->message;
        }

        public function getAccessToken() {
                return $this->accessToken;
        }

        public function getRefreshToken() {
                return $this->refreshToken;
        }

        public function getOwnerHash() { 
		return $this->ownerHash;
	}

        public function getCharacterID() {
		return $this->characterID;
	}

        public function getCharacterName() {
                if ($this->characterName == null || $this->characterName == '') {
                    $esiapi = new ESIAPI();
                    $charapi = new CharacterApi($esiapi);
                    try {
                        $charinfo = json_decode($charapi->getCharactersCharacterId($this->characterID, 'tranquility'));
                        $characterName = $charinfo->name;
                        $this->characterName = $characterName;
                    } catch (Exception $e) {
                        $this->error = true;
                        $this->message = 'Could not relove character name: '.$e->getMessage();
                        $this->log->error($this->message);
                    }
                }
                return $this->characterName;
        }

        public function getFailcount() {
		return $this->failcount;
	}

	public function isEnabled() {
		return $this->enabled;
	}

        public function hasExpired() {
                if ($this->expires < strtotime("now")) {
                        return true;
                } else {
			return false;
		}
        }

        public function getScopes() {
                if (empty($this->scopes)) {
                        $this->verify();
                }
                return $this->scopes;
        }

}
?>
