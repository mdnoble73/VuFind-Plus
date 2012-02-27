<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'sys/Proxy_Request.php';

class OAIHarvester
{
    private $host;
    private $client;
    public  $raw = false;

    public function __construct()
    {
        $this->client = new Proxy_Request();
        $this->client->setMethod(HTTP_REQUEST_METHOD_GET);
    }
    
    public function setHost($url)
    {
        $this->host = $url;
    }
    
    public function Identify()
    {
        $params = array();
        $result = $this->call('Identify', $params);
        return $result;
    }

    public function GetIdentifiers($prefix, $from = null, $until = null, $set = null, $token = null)
    {
        $params = array();
        $params['metadataPrefix'] = $prefix;
        if ($from) $params['from'] = $from;
        if ($until) $params['until'] = $until;
        if ($set) $params['set'] = $set;
        if ($token) $params['resumptionToken'] = $token;
        $result = $this->call('ListIdentifiers', $params);
        return $result;
    }

    public function GetMetadataFormats($id)
    {
        $params = array();
        $params['identifier'] = $id;
        $result = $this->call('ListMetadataFormats');
        return $result;
    }

    public function GetSets($token = null)
    {
        $params = array();
        if ($token) $params['resumptionToken'] = $token;
        $result = $this->call('ListSets', $params);
        return $result;
    }

    public function GetRecords($prefix, $from = null, $until = null, $set = null, $token = null)
    {
        $params = array();
        $params['metadataPrefix'] = $prefix;
        if ($from) $params['from'] = $from;
        if ($until) $params['until'] = $until;
        if ($set) $params['set'] = $set;
        if ($token) $params['resumptionToken'] = $token;
        $result = $this->call('ListRecords', $params);
        return $result;
    }

    public function GetRecord($id, $prefix)
    {
        $params = array();
        $params['identifier'] = $id;
        $params['metadataPrefix'] = $prefix;
        $result = $this->call('GetRecord', $params);
        return $result;
    }

    private function call($action, $params)
    {
        $url = "$this->host?verb=$action";
        if (is_array($params) && count($params)) {
            foreach($params as $field => $value) {
                $url .= "&$field=$value";
            }
        }
        $this->client->setURL($url);
        $result = $this->client->sendRequest();
        if (!PEAR::isError($result)) {
            if (!$this->raw) {
                return $this->process($this->client->getResponseBody());
            } else {
                return $this->client->getResponseBody();
            }
        } else {
            return $result;
        }
    }
    
    private function process($xmlData)
    {
        require_once 'XML/Unserializer.php';

        // Convert XML to Array
    	$unxml = new XML_Unserializer();
    	$result = $unxml->unserialize($xmlData);
    	if (!PEAR::isError($result)) {
            $result = $unxml->getUnserializedData();
            if (PEAR::isError($result)) {
                return $result;
            }
        } else {
            return $result;
        }

        // Check for errors
        if (isset($result['error'])) {
            return new PEAR_Error($result['error']);
        }

        // Return Data
        return $result;
	}

}
?>