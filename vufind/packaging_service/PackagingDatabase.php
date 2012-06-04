<?php
class PackagingDatabase {
	private static $singleton;
	private $_db;
	
	private function __construct($server, $username, $password, $database) {
		$this->_db = mysql_connect($server, $username, $password);
		if ($this->_db) {
			mysql_select_db($database, $this->_db);
		}
	}
	
	public function getRecord($distributorId, $id) {
		$sql = "SELECT * FROM acs_packaging_log WHERE distributorId = '" . mysql_real_escape_string($distributorId) . "' AND id=$id";
		$result = mysql_query($sql);
		if (!$result || mysql_num_rows($result) == 0) {
			return array(
				'success' => false,
				'error' => mysql_error()
			);
		}
		$rawRecord = mysql_fetch_assoc($result);
		return array(
			'success' => true,
			'record' => array(
				'id' => $rawRecord['id'],
				'acsError' => $rawRecord['acsError'],
				'acsId' => $rawRecord['acsId'],
				'datePackagingCompleted' => $rawRecord['packagingEndTime'],
				'status' => $rawRecord['status'],
			),
		);
	}
	
	public function getRecordsSince($distributorId, $updatedSince) {
		$sql = "SELECT * FROM acs_packaging_log WHERE distributorId = '" . mysql_real_escape_string($distributorId) . "' AND lastUpdate >= $updatedSince";
		$result = mysql_query($sql);
		if (!$result) {
			return array(
				'success' => false,
				'error' => mysql_error()
			);
		}
		$records = array();
		while ($rawRecord = mysql_fetch_assoc($result)){
			$records[] = array(
				'id' => $rawRecord['id'],
				'acsError' => $rawRecord['acsError'],
				'acsId' => $rawRecord['acsId'],
				'datePackagingCompleted' => $rawRecord['packagingEndTime'],
				'status' => $rawRecord['status'],
			);
		}
		return array(
			'success' => true,
			'records' => $records
		);
	}
	
	public function requestFileProtection($distributorId, $filename, $copies, $previousAcsId = null){
		$curTime = time();
		$record = array(
			'distributorId' =>  "'" . mysql_real_escape_string($distributorId) . "'",
			'copies' => mysql_real_escape_string($copies),
			'filename' => "'" . mysql_real_escape_string($filename) . "'",
			'created' => $curTime,
			'lastUpdate' => $curTime,
			'status' => "'pending'"
		);
		if (isset($previousAcsId) && strlen($previousAcsId) > 0){
			$record['previousAcsId'] = mysql_real_escape_string($previousAcsId);
		}
		$result = $this->_insert($record);
		if ($result === false) {
			return array(
				'success' => false,
				'error' => "Could not add file to database " . mysql_error()
			);
		}
		$packagingId = mysql_insert_id($this->_db);
		return array(
			'success' => true,
			'packagingId' => $packagingId
		);
	}
	
	public function saveRecord($data) {
		$update = (isset($data['id']) && is_numeric($data['id']));
		$record = array(
			'distributorId' => "'" . mysql_real_escape_string($data['distributorId']) . "'",
			'copies' => mysql_real_escape_string($data['copies']),
			'filename' => "'" . mysql_real_escape_string($data['filename']) . "'",
			'created' => "'" . mysql_real_escape_string($data['created']) . "'",
			'lastUpdate' => "'" . mysql_real_escape_string($data['lastUpdate']) . "'",
			'packagingStartTime' => "'" . mysql_real_escape_string($data['packagingStartTime']) . "'",
			'packagingEndTime' => "'" . mysql_real_escape_string($data['packagingEndTime']) . "'",
			'acsError' => "'" . mysql_real_escape_string($data['acsError']) . "'",
			'acsId' => mysql_real_escape_string($data['acsId']),
			'status' => "'" . mysql_real_escape_string($data['status']) . "'"
		);
		if (isset($data['packagingId'])){
			$record['packagingId'] = mysql_real_escape_string($data['packagingId']);
		}
		$result = $update ? $this->_update($record) : $this->_insert($record);
		if ($result === false) {
			return false;
		}
		return ($update ? $data['id'] : mysql_insert_id($this->_db));
	}
	
	private function _update($record) {
		$sql = "UPDATE acs_packaging_log SET ";
		foreach ($record as $field=>$value) {
			if ($value == "''" || empty($value)) {
				$value = 'NULL';
			}
			if ($field != 'packagingId') {
				$sql .= $field . '=' . $value . ',';
			}
		}
		$sql = trim($sql, ',') . ' WHERE packagingId=' . $record['packagingId'];
		return mysql_query($sql, $this->_db);
	}
	
	private function _insert($record) {
		$fields = array_keys($record);
		unset($fields['id']);
		$values = array();
		foreach ($fields as $field) {
			$value = $record[$field];
			if ($value == "''" || empty($value)) {
				$value = 'NULL';
			}
			$values[] = $value;
		}
		$sql = "INSERT INTO acs_packaging_log(" 
			. implode(',', $fields)
			. ") VALUES ("
			. implode(',', $values)
			. ")";
		return mysql_query($sql, $this->_db);
	}
	
	public static function connect($server, $username, $password, $database) {
		if (!self::$singleton) {
			$instance = new PackagingDatabase($server, $username, $password, $database);
			if (!$instance->_db) {
				return false;
			}
			self::$singleton = $instance;
		}
		return self::$singleton;
	}
	
	public function close() {
		mysql_close($this->_db);
	}
}
?>