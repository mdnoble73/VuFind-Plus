<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';

require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
$driver = OverDriveDriverFactory::getDriver();

function easy_printr($title, $section, &$var) {
	echo "<a onclick='$(\"#{$section}\").toggle();return false;' href='#'>{$title}</a>";
	echo "<pre style='display:none' id='{$section}'>";
	print_r($var);
	echo '</pre>';
}
echo ("<html>");
echo ("<head><script type='text/javascript' src='/js/jquery-1.9.1.min.js'></script></head>");

echo ("<body>");

echo '<p>Add url paramater id={OverdriveProductID} to see a specific Product</p>';

$libraryInfo = $driver->getLibraryAccountInformation();
easy_printr('Library Account Information', 'libraryAccountInfo', $libraryInfo);

echo "<h1>Advantage Accounts</h1>";

$advantageAccounts = null;
try {
	$advantageAccounts = $driver->getAdvantageAccountInformation();
	if ($advantageAccounts) {
		foreach ($advantageAccounts->advantageAccounts as $accountInfo) {
			echo $accountInfo->name . ' - ' . $accountInfo->collectionToken . '<br/>';
		}
	}
} catch (Exception $e) {
	echo 'Error retrieving Advantage Info';
}

$productKey = $libraryInfo->collectionToken;

echo"<h1>{$libraryInfo->name}</h1>";

if (!empty($_REQUEST['id'])) {
	$overDriveId = $_REQUEST['id'];
} else {
	$overDriveId = "24f6c1d4-64c1-4d79-bd9a-610bc22c4d59";
}

echo "<h2>Metadata</h2>";
echo "<h3>Metadata for $overDriveId</h3>";
$metadata    = $driver->getProductMetadata($overDriveId, $productKey);
easy_printr("Metadata for $overDriveId in shared collection", "metadata_{$overDriveId}_{$productKey}", $metadata);
if ($advantageAccounts) {
	foreach ($advantageAccounts->advantageAccounts as $accountInfo) {
		echo("<h3>Metadata - {$accountInfo->name}</h3>");
		$metadata = $driver->getProductMetadata($overDriveId, $accountInfo->collectionToken);
		if ($metadata){
			easy_printr("Metadata response", "metadata_{$overDriveId}_{$accountInfo->collectionToken}", $metadata);
		}else{
			echo("No metadata<br/>");
		}
	}
}

echo "<h2>Availability</h2>";
echo("<h3>Availability - {$libraryInfo->name}</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability($overDriveId, $productKey);
if ($availability && !isset($availability->errorCode)) {
	echo("Copies Owned {$availability->copiesOwned }<br/>");
	echo("Available Copies {$availability->copiesAvailable }<br/>");
	echo("Num Holds {$availability->numberOfHolds }<br/>");
	easy_printr("Availability response", "availability_{$overDriveId}_{$productKey}", $availability);
}else{
	echo("Not owned<br/>");
	if ($availability){
		easy_printr("Availability response", "availability_{$overDriveId}_{$productKey}", $availability);
	}
}

if ($advantageAccounts) {
	foreach ($advantageAccounts->advantageAccounts as $accountInfo) {
		echo("<h3>Availability - {$accountInfo->name}</h3>");
		$availability = $driver->getProductAvailability($overDriveId, $accountInfo->collectionToken);
		if ($availability && !isset($availability->errorCode)){
			echo("Copies Owned {$availability->copiesOwned }<br/>");
			echo("Available Copies {$availability->copiesAvailable }<br/>");
			echo("Num Holds {$availability->numberOfHolds }<br/>");
			easy_printr("Availability response", "availability_{$overDriveId}_{$accountInfo->collectionToken}", $availability);
		}else{
			echo("Not owned<br/>");
			if ($availability){
				easy_printr("Availability response", "availability_{$overDriveId}_{$productKey}", $availability);
			}
		}
	}
}

echo ("</body>");
echo ("</html>");

function showProductInfo($driver, $productUrl){
	$now = time();
	$productInfo = $driver->getProductsInAccount($productUrl);
	echo("Total Items = {$productInfo->totalItems}");
	$batchSize = $productInfo->totalItems;

	$curProduct = 0;
	echo("<table border='1'>");
	//echo("<thead><tr><th>ID</th><th>Title</th><th>Formats</th><th>Owned</th><th>Available</th><th>Num Holds</th></tr></thead>");
	echo("<thead><tr><th>#</th><th>ID</th><th>Title</th><th>Formats</th><th>Availability URL</th></tr></thead>");
	echo("<tbody>");
	//for ($i = 0; $i < $productInfo->totalItems; $i += $batchSize){
	for ($i = 0; $i < 50; $i += $batchSize){
		set_time_limit(120);
		$productInfo = $driver->getProductsInAccount($productUrl, $i, $batchSize);
		//print_r($productInfo);

		foreach($productInfo->products as $product){
			echo("<tr>");
			echo("<td>" . ++$curProduct . "</td>");
			//echo("<li><img src='{$product->images->thumbnail->href}' /> {$product->id} - {$product->title} </li>");
			echo("<td>{$product->id}</td><td>{$product->title} </td>");
			echo("<td>");
			foreach ($product->formats as $format){
				echo("{$format->name}, ");
			}
			echo("</td>");
			echo("<td>{$product->links->availability->href}</td>");
			/*$availability = $driver->getProductAvailability($product->links->availability->href);
			echo("<td>{$availability->copiesOwned }</td>");
			echo("<td>{$availability->copiesAvailable }</td>");
			echo("<td>{$availability->numberOfHolds }</td>");*/
			//print_r($product);
			echo("</tr>");
		}

		flush();
	}
	echo("</tbody></table>");
	$end = time();
	echo ("Time to load = " . ($end - $now) . " seconds");
}