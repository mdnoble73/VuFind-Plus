<?php
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'bootstrap.php';

require_once ROOT_DIR . '/Drivers/OverDriveDriverFactory.php';
$driver = OverDriveDriverFactory::getDriver();

//Connect to the patron API
echo("<h2>Patron API tests</h2>");
$user = new User();
$user->cat_username = '190007357';
$user->cat_password = '7537';
$user->overdriveEmail = 'spam@marmot.org';
echo("<h3>Getting patron information</h3>");
$accountDetails = $driver->getOverDriveSummary($user);
//$patronData = $driver->_callPatronUrl($user->cat_password, null, 'http://api.mock.overdrive.com/v2/patrons/me');
print_r($accountDetails);

/*echo("<h3>Getting holds</h3>");
$patronData = $driver->getOverDriveHolds($user, 'http://api.mock.overdrive.com/v2/patrons/me/holds');
print_r($patronData);

echo("<h3>Placing Hold</h3>");
$placeHoldResult = $driver->placeOverDriveHold('0D85564B-A4B3-43D5-875D-1DF3CA06AE65', null, $user);
print_r($placeHoldResult);

echo("<h3>Cancelling Hold</h3>");
$cancelResult = $driver->cancelOverDriveHold($user, '0D85564B-A4B3-43D5-875D-1DF3CA06AE65', null);
print_r($cancelResult);

echo("<h3>Cancelling Non Existent Hold</h3>");
$cancelResult = $driver->cancelOverDriveHold($user, '12F25D77-A306-42E2-92FA-0CDA71915A57', null);
print_r($cancelResult);

echo("<h3>Getting checkouts</h3>");
$patronData = $driver->getOverDriveCheckedOutItems($user, 'http://api.mock.overdrive.com/v2/patrons/me/checkouts');
print_r($patronData);

echo("<h3>Checking Out title (format locked in)</h3>");
$checkoutResult = $driver->checkoutOverDriveItem('12F25D77-A306-42E2-92FA-0CDA71915A57', 'ebook-epub-adobe', null, $user);
print_r($checkoutResult);

echo("<h3>Checking Out title (format not locked in)</h3>");
$checkoutResult = $driver->checkoutOverDriveItem('E72A25BF-2B10-49EA-8887-5B70B78537A8', null, null, $user);
print_r($checkoutResult);

echo("<h3>Lock in a format for a title</h3>");
$selectFormatResult = $driver->selectOverDriveDownloadFormat('76c1b7d0-17f4-4c05-8397-c66c17411584', 'ebook-epub-adobe', $user);
print_r($selectFormatResult);

echo("<h3>Return title</h3>");
$returnResult = $driver->returnOverDriveItem('E72A25BF-2B10-49EA-8887-5B70B78537A8', null, $user);
print_r($returnResult);

echo("<h3>Get Download Link for a title</h3>");
$downloadLink = $driver->getDownloadLink('12F25D77-A306-42E2-92FA-0CDA71915A57', 'ebook-epub-adobe', $user);
print_r($downloadLink);
echo("<br /><a href='" . $downloadLink['downloadUrl'] ."'>Download Here</a>");

echo("<h3>Get OverDrive Read link for a title</h3>");
$downloadLink = $driver->getDownloadLink('12F25D77-A306-42E2-92FA-0CDA71915A57', 'ebook-overdrive', $user);
print_r($downloadLink);
echo("<br /><a href='" . $downloadLink['downloadUrl'] ."'>Download Here</a>");

echo("<h3>Return title</h3>");
$returnResult = $driver->returnOverDriveItem('12F25D77-A306-42E2-92FA-0CDA71915A57', null, $user);
print_r($returnResult);

echo("<h2>End patron API tests</h2>");*/


/*$libraryInfo = $driver->getLibraryAccountInformation();
print_r($libraryInfo);
echo("<h1>{$libraryInfo->name}</h1>");

echo("<h2>Products</h2>");
echo("Products link {$libraryInfo->links->products->href}<br/>");
//showProductInfo($driver, $libraryInfo->links->products->href);

$productInfo = $driver->getProductsInAccount($libraryInfo->links->products->href);
echo("<h2>First Product Details</h2>");
$firstProduct = reset($productInfo->products);
echo("{$firstProduct->title}: {$firstProduct->subtitle}<br/>");
echo("By {$firstProduct->primaryCreator->name}<br/>");
print_r($firstProduct);

//echo("<h2>Advantage Product Details</h2>");
//$productInfo = $driver->_callUrl("http://api.overdrive.com/v1/libraries/1201/advantageAccounts/50");
//print_r($productInfo);

//echo("<h2>Bud Unique Products</h2>");
//$productInfo = $driver->_callUrl("http://api.overdrive.com/v1/collections/L1BUwYAAA2r/products");
//print_r($productInfo);

echo("<h3>Metadata</h3>");
echo($firstProduct->links->metadata->href);
//$metadata = $driver->getProductMetadata($firstProduct->links->metadata->href);
$metadata = $driver->getProductMetadata("cda4632c-0593-46e7-94a4-1e4c4451da09", "L1BMAEAAA2k");
print_r($metadata);*/



/*echo("<h3>Availability - MDL</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L1BMAEAAA2k");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");


echo("<h3>Availability - Wilkinson</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L2BMAEAALoBAAA1X");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
//print_r($availability);

echo("<h3>Availability - Pitkin</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L2BMAEAALoBAAA1X");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
//print_r($availability);

echo("<h3>Availability - Eagle</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L2BMAEAANIBAAA1R");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");
//print_r($availability);

echo("<h3>Availability - Grand County</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L2BMAEAABUGAAA1w");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");

echo("<h3>Availability - Bud Werner</h3>");
//echo("{$firstProduct->links->availability->href}<br/>");
$availability = $driver->getProductAvailability("1e8326f9-d42f-4cf1-afec-c2af4f4807d9", "L1BUwYAAA2r");
echo("Copies Owned {$availability->copiesOwned }<br/>");
echo("Available Copies {$availability->copiesAvailable }<br/>");
echo("Num Holds {$availability->numberOfHolds }<br/>");


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
}*/


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