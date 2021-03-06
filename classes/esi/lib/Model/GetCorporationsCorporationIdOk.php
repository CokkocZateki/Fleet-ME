<?php
/**
 * GetCorporationsCorporationIdOk
 *
 * PHP version 5
 *
 * @category Class
 * @package  Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * EVE Swagger Interface
 *
 * An OpenAPI for EVE Online
 *
 * OpenAPI spec version: 0.4.1.dev1
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Swagger\Client\Model;

use \ArrayAccess;

/**
 * GetCorporationsCorporationIdOk Class Doc Comment
 *
 * @category    Class */
 // @description 200 ok object
/** 
 * @package     Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache Licene v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class GetCorporationsCorporationIdOk implements ArrayAccess
{
    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'get_corporations_corporation_id_ok';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = array(
        'alliance_id' => 'int',
        'ceo_id' => 'int',
        'corporation_name' => 'string',
        'member_count' => 'int',
        'ticker' => 'string'
    );

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = array(
        'alliance_id' => 'alliance_id',
        'ceo_id' => 'ceo_id',
        'corporation_name' => 'corporation_name',
        'member_count' => 'member_count',
        'ticker' => 'ticker'
    );

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = array(
        'alliance_id' => 'setAllianceId',
        'ceo_id' => 'setCeoId',
        'corporation_name' => 'setCorporationName',
        'member_count' => 'setMemberCount',
        'ticker' => 'setTicker'
    );

    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = array(
        'alliance_id' => 'getAllianceId',
        'ceo_id' => 'getCeoId',
        'corporation_name' => 'getCorporationName',
        'member_count' => 'getMemberCount',
        'ticker' => 'getTicker'
    );

    public static function getters()
    {
        return self::$getters;
    }

    

    

    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = array();

    /**
     * Constructor
     * @param mixed[] $data Associated array of property value initalizing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['alliance_id'] = isset($data['alliance_id']) ? $data['alliance_id'] : null;
        $this->container['ceo_id'] = isset($data['ceo_id']) ? $data['ceo_id'] : null;
        $this->container['corporation_name'] = isset($data['corporation_name']) ? $data['corporation_name'] : null;
        $this->container['member_count'] = isset($data['member_count']) ? $data['member_count'] : null;
        $this->container['ticker'] = isset($data['ticker']) ? $data['ticker'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = array();
        if ($this->container['ceo_id'] === null) {
            $invalid_properties[] = "'ceo_id' can't be null";
        }
        if ($this->container['corporation_name'] === null) {
            $invalid_properties[] = "'corporation_name' can't be null";
        }
        if ($this->container['member_count'] === null) {
            $invalid_properties[] = "'member_count' can't be null";
        }
        if ($this->container['ticker'] === null) {
            $invalid_properties[] = "'ticker' can't be null";
        }
        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properteis are valid
     */
    public function valid()
    {
        if ($this->container['ceo_id'] === null) {
            return false;
        }
        if ($this->container['corporation_name'] === null) {
            return false;
        }
        if ($this->container['member_count'] === null) {
            return false;
        }
        if ($this->container['ticker'] === null) {
            return false;
        }
        return true;
    }


    /**
     * Gets alliance_id
     * @return int
     */
    public function getAllianceId()
    {
        return $this->container['alliance_id'];
    }

    /**
     * Sets alliance_id
     * @param int $alliance_id id of alliance that corporation is a member of, if any
     * @return $this
     */
    public function setAllianceId($alliance_id)
    {
        $this->container['alliance_id'] = $alliance_id;

        return $this;
    }

    /**
     * Gets ceo_id
     * @return int
     */
    public function getCeoId()
    {
        return $this->container['ceo_id'];
    }

    /**
     * Sets ceo_id
     * @param int $ceo_id ceo_id integer
     * @return $this
     */
    public function setCeoId($ceo_id)
    {
        $this->container['ceo_id'] = $ceo_id;

        return $this;
    }

    /**
     * Gets corporation_name
     * @return string
     */
    public function getCorporationName()
    {
        return $this->container['corporation_name'];
    }

    /**
     * Sets corporation_name
     * @param string $corporation_name the full name of the corporation
     * @return $this
     */
    public function setCorporationName($corporation_name)
    {
        $this->container['corporation_name'] = $corporation_name;

        return $this;
    }

    /**
     * Gets member_count
     * @return int
     */
    public function getMemberCount()
    {
        return $this->container['member_count'];
    }

    /**
     * Sets member_count
     * @param int $member_count member_count integer
     * @return $this
     */
    public function setMemberCount($member_count)
    {
        $this->container['member_count'] = $member_count;

        return $this;
    }

    /**
     * Gets ticker
     * @return string
     */
    public function getTicker()
    {
        return $this->container['ticker'];
    }

    /**
     * Sets ticker
     * @param string $ticker the short name of the corporation
     * @return $this
     */
    public function setTicker($ticker)
    {
        $this->container['ticker'] = $ticker;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
    }
}


