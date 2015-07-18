<?php
/**
 * Responsible for instantiating Catalog Connections to minimize making multiple concurrent connections
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/26/15
 * Time: 8:38 PM
 */

class CatalogFactory {
	/** @var array An array of connections keyed by driver name */
	private static $catalogConnections = array();

	/**
	 * @param string|null $driver
	 * @return CatalogConnection
	 */
	public static function getCatalogConnectionInstance($driver = null){
		require_once ROOT_DIR . '/CatalogConnection.php';
		if ($driver == null){
			global $configArray;
			$driver = $configArray['Catalog']['driver'];
		}
		if (isset(CatalogFactory::$catalogConnections[$driver])){
			return CatalogFactory::$catalogConnections[$driver];
		}else{
			CatalogFactory::$catalogConnections[$driver] = new CatalogConnection($driver);
			return CatalogFactory::$catalogConnections[$driver];
		}
	}
}