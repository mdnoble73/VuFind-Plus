<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';

require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
$driver = OverDriveDriverFactory::getDriver();

function easy_printr(&$var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

$libraryInfo = $driver->getLibraryAccountInformation();
easy_printr($libraryInfo);

echo "<h1>Advantage Accounts</h1>";

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

echo"<h1>{$libraryInfo->name}</h1>",
	"<h2>Products</h2>",
	"Products link {$libraryInfo->links->products->href}<br/>",
	"Product Key $productKey<br/>";
//showProductInfo($driver, $libraryInfo->links->products->href);

$productInfo = $driver->getProductsInAccount($libraryInfo->links->products->href);
$firstProduct = reset($productInfo->products);

$subtitle = isset($firstProduct->subtitle) ? ": {$firstProduct->subtitle}" : '';

echo "<h2>First Product Details</h2>",
	"{$firstProduct->title}$subtitle<br/>",
	"By {$firstProduct->primaryCreator->name}<br/>";

easy_printr($firstProduct);

//echo("<h2>Advantage Product Details</h2>");
//$productInfo = $driver->_callUrl("http://api.overdrive.com/v1/libraries/1201/advantageAccounts/50");
//print_r($productInfo);

//echo("<h2>Bud Unique Products</h2>");
//$productInfo = $driver->_callUrl("http://api.overdrive.com/v1/collections/L1BUwYAAA2r/products");
//print_r($productInfo);

echo "<h3>Metadata</h3>",
'<p>Add url paramater id={OverdriveProductID}to this page to see a specific Product</p>';
//	$firstProduct->links->metadata->href;

//$metadata = $driver->getProductMetadata($firstProduct->links->metadata->href);
//$metadata = $driver->getProductMetadata("cda4632c-0593-46e7-94a4-1e4c4451da09", "L1BMAEAAA2k");

if (!empty($_REQUEST['id'])) {
	$overDriveId = $_REQUEST['id'];
} else {
	$overDriveId = "A747D620-96F9-42BC-B4E1-4830EC9D3C9E";
}
$metadata    = $driver->getProductMetadata($overDriveId, $productKey);

easy_printr($metadata);


//Get Update Batch Instead
echo "<h2>OverDrive Extract Batch</h2>";


require_once ROOT_DIR . '/sys/Variable.php';
$lastOverDriveExtractVariable = new Variable();
$lastOverDriveExtractVariable->name = 'last_overdrive_extract_time';
if ($lastOverDriveExtractVariable->find(true)) {
	$lastUpdateTime = date(DATE_W3C, $lastOverDriveExtractVariable->value/1000);

}

$url = "http://api.overdrive.com/v1/collections/v1L1ByAAAAA2r/products?lastupdatetime=$lastUpdateTime&offset=0&limit=300";
echo "<p>$url</p>";

$productInfo = $driver->_callUrl($url);
easy_printr($productInfo);





echo("<h3>Availability - MDL</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L1BMAEAAA2k");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Wilkinson</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L2BMAEAALoBAAA1X");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Pitkin</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L2BMAEAALoBAAA1X");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Eagle</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L2BMAEAANIBAAA1R");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Grand County</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L2BMAEAABUGAAA1w");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Garfield</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "v1L1BBggAAA2G");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

echo("<h3>Availability - Bud Werner</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("e21c7ba3-7340-4140-b483-c58aab6316f6", "L1BUwYAAA2r");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
easy_printr($availability);

$advantageInfo = ($driver->getAdvantageAccountInformation());
echo("<h2>Advantage Accounts</h2>");
//print_r($advantageInfo);
foreach ($advantageInfo->advantageAccounts as $advantageAccount){
	echo("<h3>{$advantageAccount->name} - {$advantageAccount->links->self->href}</h3>");
	//print_r($advantageAccount);
	$selfAdvantageInfo = $driver->_callUrl($advantageAccount->links->self->href);
	//print_r($selfAdvantageInfo);
	echo("Library API Key = {$selfAdvantageInfo->links->products->href}");
	//showProductInfo($driver, "{$advantageAccount->links->products->href}");
}


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